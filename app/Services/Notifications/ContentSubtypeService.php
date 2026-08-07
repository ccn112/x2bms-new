<?php

namespace App\Services\Notifications;

use App\Enums\CommunicationContentType as CT;
use App\Models\Event;
use App\Models\Notification;
use App\Models\Poll;
use App\Models\PollOption;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Nội dung theo loại (BQL-NOTI-02). Announcement/News lưu meta trên notifications;
 * Event/Poll THAM CHIẾU entity canonical (không nhân đôi domain — ADR-002): tạo/cập nhật
 * Event/Poll trong scope tenant/project của chiến dịch rồi gắn entity_type/entity_id.
 * Đăng ký/vote vẫn canonical ở event_registrations/poll_votes (write path ở resident API).
 */
class ContentSubtypeService
{
    /** Validate field bắt buộc theo loại (spec 04). Throw nếu thiếu. */
    public function validate(CT $type, array $data): void
    {
        match ($type) {
            CT::Event => $this->requireKeys($data, ['venue', 'starts_at'], 'Sự kiện'),
            CT::Poll => $this->validatePoll($data),
            CT::News => $this->requireKeys($data, ['category'], 'Tin tức'),
            CT::Announcement => null,
        };
    }

    /**
     * Đồng bộ entity con + meta cho chiến dịch. Trả về Notification đã set entity/meta
     * (chưa save — caller save trong cùng transaction wizard/seeder).
     */
    public function syncEntity(Notification $n, CT $type, array $data): Notification
    {
        return match ($type) {
            CT::Event => $this->syncEvent($n, $data),
            CT::Poll => $this->syncPoll($n, $data),
            CT::News => tap($n, fn (Notification $x) => $x->content_meta = $this->newsMeta($data)),
            CT::Announcement => $n,
        };
    }

    private function syncEvent(Notification $n, array $data): Notification
    {
        $startsAt = $this->toDate($data['starts_at']);
        $endsAt = isset($data['ends_at'])
            ? $this->toDate($data['ends_at'])
            : ($startsAt && isset($data['duration_minutes']) ? $startsAt->copy()->addMinutes((int) $data['duration_minutes']) : null);

        $attrs = [
            'tenant_id' => $n->tenant_id,
            'project_id' => $n->project_id,
            'title' => $n->title,
            'description' => $n->body,
            'location' => $data['venue'] ?? null,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'capacity' => $data['capacity'] ?? null,
            'registration_deadline' => isset($data['registration_deadline']) ? $this->toDate($data['registration_deadline']) : null,
            'allow_guests' => (bool) ($data['allow_guests'] ?? false),
            'max_guests' => (int) ($data['max_guests'] ?? 0),
            'fee_amount' => (int) ($data['fee_amount'] ?? 0),
            'qr_checkin' => (bool) ($data['qr_checkin'] ?? false),
            'status' => $data['status'] ?? 'upcoming',
            'registration_status' => $data['registration_status'] ?? 'open',
        ];

        $event = $n->contentEvent();
        if ($event) {
            $event->fill($attrs)->save();
        } else {
            $event = Event::create($attrs);
        }

        $n->entity_type = 'event';
        $n->entity_id = $event->id;

        return $n;
    }

    private function syncPoll(Notification $n, array $data): Notification
    {
        $options = $data['options'] ?? [];
        $attrs = [
            'tenant_id' => $n->tenant_id,
            'project_id' => $n->project_id,
            'question' => $data['question'] ?? $n->title,
            'summary' => $data['summary'] ?? $n->summary,
            'type' => ! empty($data['allow_multiple']) ? 'multiple' : 'single',
            'status' => $data['status'] ?? 'draft',
            'anonymous' => (bool) ($data['anonymous'] ?? false),
            'vote_scope' => $data['vote_scope'] ?? 'resident',
            'allow_change_vote' => (bool) ($data['allow_change_vote'] ?? false),
            'max_choices' => $data['max_choices'] ?? (! empty($data['allow_multiple']) ? count($options) : 1),
            'result_visibility' => $data['result_visibility'] ?? 'after_vote',
            'opens_at' => isset($data['opens_at']) ? $this->toDate($data['opens_at']) : null,
            'closes_at' => isset($data['closes_at']) ? $this->toDate($data['closes_at']) : null,
        ];

        DB::transaction(function () use ($n, $attrs, $options, &$poll) {
            $poll = $n->contentPoll();
            if ($poll) {
                $poll->fill($attrs)->save();
                $poll->options()->delete();
            } else {
                $poll = Poll::create($attrs);
            }
            foreach (array_values($options) as $i => $opt) {
                PollOption::create([
                    'poll_id' => $poll->id,
                    'option_key' => $opt['key'] ?? (string) ($i + 1),
                    'label' => is_array($opt) ? ($opt['label'] ?? '') : (string) $opt,
                    'sort' => $i,
                ]);
            }
            $n->entity_type = 'poll';
            $n->entity_id = $poll->id;
        });

        return $n;
    }

    /** @return array<string,mixed> */
    private function newsMeta(array $data): array
    {
        return array_filter([
            'category' => $data['category'] ?? null,
            'author' => $data['author'] ?? null,
            'visibility' => $data['visibility'] ?? 'resident', // resident|public
            'featured' => (bool) ($data['featured'] ?? false),
            'slug' => $data['slug'] ?? null,
            'publish_at' => $data['publish_at'] ?? null,
        ], fn ($v) => $v !== null);
    }

    private function validatePoll(array $data): void
    {
        $options = $data['options'] ?? [];
        if (empty($data['question'])) {
            throw new InvalidArgumentException('Poll cần câu hỏi.');
        }
        if (count($options) < 2) {
            throw new InvalidArgumentException('Poll cần tối thiểu 2 lựa chọn.');
        }
        if (! in_array($data['vote_scope'] ?? 'resident', Poll::VOTE_SCOPES, true)) {
            throw new InvalidArgumentException('Phạm vi phiếu không hợp lệ.');
        }
    }

    private function requireKeys(array $data, array $keys, string $label): void
    {
        foreach ($keys as $key) {
            if (! isset($data[$key]) || $data[$key] === '' || $data[$key] === null) {
                throw new InvalidArgumentException("{$label} thiếu trường bắt buộc: {$key}.");
            }
        }
    }

    private function toDate(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        return $value ? Carbon::parse($value) : null;
    }
}
