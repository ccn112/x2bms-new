<?php

namespace Database\Seeders;

use App\Enums\CommunicationContentType as CT;
use App\Models\Building;
use App\Models\Notification;
use App\Models\NotificationAudienceGroup;
use App\Models\NotificationChannel;
use App\Models\NotificationDeliveryLog;
use App\Models\Project;
use App\Models\Resident;
use App\Models\Tenant;
use App\Services\Notifications\AudienceResolver;
use App\Services\Notifications\ContentSubtypeService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Demo Truyền thông BQL (BQL-NOTI) — 12 thông báo / 8 tin / 6 sự kiện / 6 poll + nhóm
 * người nhận + template + mẫu gửi. Idempotent theo (tenant, code=SEED:<seed_key>).
 * Non-production + X2_DEMO_SEED. Provider FAKE (chỉ ghi delivery rows, KHÔNG gửi mạng).
 * Map dữ liệu seed sang demo THẬT (mã tòa S1/S2… → tòa demo hiện có).
 */
final class CommunicationDemoSeeder extends Seeder
{
    private Tenant $tenant;
    private ?Project $project;
    /** @var \Illuminate\Support\Collection<int,Building> */
    private $buildings;
    /** @var \Illuminate\Support\Collection<int,Resident> */
    private $residents;
    private string $dir;

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('CommunicationDemoSeeder bị chặn ngoài môi trường non-production.');
        }
        if (! config('x2.demo_seed_enabled', false)) {
            $this->command?->warn('Đặt X2_DEMO_SEED=true để chạy CommunicationDemoSeeder.');

            return;
        }

        $this->dir = database_path('seeders/data/communication');
        $this->resolveDemoContext();

        DB::transaction(function () {
            $groups = $this->seedAudienceGroups();
            $this->seedTemplates();
            $this->seedContent('announcements.json', CT::Announcement, $groups);
            $this->seedContent('news.json', CT::News, $groups);
            $this->seedContent('events.json', CT::Event, $groups);
            $this->seedContent('polls.json', CT::Poll, $groups);
            $this->seedDeliverySamples();
        });

        $this->command?->info('CommunicationDemoSeeder: xong (demo Truyền thông BQL).');
    }

    private function resolveDemoContext(): void
    {
        $this->tenant = Tenant::where('code', 'T-X2-DEMO')->first()
            ?? Tenant::orderBy('id')->first()
            ?? throw new RuntimeException('Chưa có tenant demo — chạy DemoDataSeeder trước.');

        $this->buildings = Building::withoutGlobalScopes()->where('tenant_id', $this->tenant->id)->orderBy('id')->get();
        if ($this->buildings->isEmpty()) {
            throw new RuntimeException('Tenant demo chưa có tòa nhà — chạy DemoDataSeeder trước.');
        }
        $this->project = Project::withoutGlobalScopes()->where('tenant_id', $this->tenant->id)->orderBy('id')->first();
        $this->residents = Resident::withoutGlobalScopes()->where('tenant_id', $this->tenant->id)->orderBy('id')->get();
        if ($this->residents->isEmpty()) {
            throw new RuntimeException('Tenant demo chưa có cư dân — chạy DemoDataSeeder trước.');
        }
    }

    /** @return array<string,NotificationAudienceGroup> keyed by seed_key */
    private function seedAudienceGroups(): array
    {
        $groups = [];
        foreach ($this->json('audience_groups.json') as $g) {
            $rule = $this->remapRule($g['rules'] ?? []);
            $group = NotificationAudienceGroup::withoutGlobalScopes()->updateOrCreate(
                ['tenant_id' => $this->tenant->id, 'seed_key' => $g['seed_key']],
                ['name' => $g['name'], 'rule' => $rule, 'created_by_id' => null],
            );
            $groups[$g['seed_key']] = $group;
        }

        return $groups;
    }

    private function seedTemplates(): void
    {
        foreach ($this->json('channel_templates.json') as $t) {
            DB::table('notification_templates')->updateOrInsert(
                ['code' => $t['seed_key']],
                [
                    'category' => $t['channel'],
                    'risk' => 'low',
                    'allowed_variables' => json_encode($t['variables'] ?? [], JSON_UNESCAPED_UNICODE),
                    'active' => in_array($t['status'] ?? 'active', ['active'], true),
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }

    /** @param array<string,NotificationAudienceGroup> $groups */
    private function seedContent(string $file, CT $type, array $groups): void
    {
        foreach ($this->json($file) as $item) {
            $group = $groups[$item['audience_group_key'] ?? ''] ?? null;
            $rule = $group?->rule ?? [];

            [$workflow, $status, $publishAt, $publishedAt] = $this->resolveLifecycle($type, $item);

            $n = Notification::withoutGlobalScopes()->updateOrCreate(
                ['tenant_id' => $this->tenant->id, 'code' => 'SEED:'.$item['seed_key']],
                [
                    'owner_level' => 'project',
                    'project_id' => $this->project?->id,
                    'content_type' => $type->value,
                    'type' => $type === CT::Announcement ? 'announcement' : $type->value,
                    'category' => $item['category'] ?? null,
                    'title' => $item['title'],
                    'summary' => $item['summary'] ?? null,
                    'body' => $item['body'] ?? ($item['summary'] ?? $item['title']),
                    'priority' => $item['priority'] ?? 'normal',
                    'workflow_status' => $workflow,
                    'status' => $status,
                    'requires_ack' => (bool) ($item['require_read_ack'] ?? false),
                    'allow_feedback' => (bool) ($item['allow_feedback'] ?? false),
                    'is_pinned' => (bool) ($item['pin_in_app'] ?? false),
                    'cta_label' => $item['cta']['label'] ?? null,
                    'cta_target' => $item['cta']['target'] ?? null,
                    'audience_rule' => $rule,
                    'publish_at' => $publishAt,
                    'published_at' => $publishedAt,
                    'expires_at' => isset($item['expires_in_days']) ? now()->addDays((int) $item['expires_in_days']) : null,
                    'created_by_id' => null,
                ],
            );

            // Subtype: link Event/Poll canonical.
            if ($type === CT::Event) {
                app(ContentSubtypeService::class)->syncEntity($n, CT::Event, $this->eventData($item));
                $n->save();
            } elseif ($type === CT::Poll) {
                app(ContentSubtypeService::class)->syncEntity($n, CT::Poll, $this->pollData($item));
                $n->save();
                $this->applyPollVotes($n, $item);
            } elseif ($type === CT::News) {
                $n->content_meta = ['category' => $item['category'] ?? 'news', 'featured' => (bool) ($item['featured'] ?? false)];
                $n->save();
            }

            // Channels.
            $n->channels()->delete();
            foreach (($item['channels'] ?? ['app']) as $ch) {
                NotificationChannel::create(['notification_id' => $n->id, 'channel' => $ch, 'enabled' => true]);
            }

            // Resolve recipients cho campaign đã/đang phát hành (để màn hình có dữ liệu thật).
            if (in_array($workflow, ['sent', 'completed', 'partially_sent', 'sending'], true)) {
                $n->load('channels');
                $count = app(AudienceResolver::class)->resolve($n->fresh('channels'));
                // read_count demo ~60% (deterministic).
                $n->forceFill(['read_count' => (int) floor($count * 0.6)])->save();
            }
        }
    }

    /** Map trạng thái seed → [workflow_status, status, publish_at, published_at]. */
    private function resolveLifecycle(CT $type, array $item): array
    {
        $sched = $item['schedule'] ?? null;
        $publishAt = null;
        $publishedAt = null;
        if (is_array($sched)) {
            if (isset($sched['send_in_days'])) {
                $publishAt = now()->addDays((int) $sched['send_in_days']);
            } elseif (isset($sched['send_in_hours'])) {
                $publishAt = now()->addHours((int) $sched['send_in_hours']);
            } elseif (isset($sched['sent_days_ago'])) {
                $publishedAt = now()->subDays((int) $sched['sent_days_ago']);
            }
        }

        $raw = $item['status'] ?? 'draft';
        $workflow = match ($raw) {
            'draft' => 'draft',
            'pending_approval' => 'pending_approval',
            'approved' => 'approved',
            'scheduled' => 'scheduled',
            'partially_sent' => 'partially_sent',
            'completed', 'sent' => 'completed',
            // event/poll statuses → map về workflow tương đương
            'open_registration', 'full_waitlist', 'open', 'closed' => 'completed',
            'cancelled' => 'cancelled',
            default => 'draft',
        };

        $status = match ($workflow) {
            'scheduled' => 'scheduled',
            'completed', 'partially_sent', 'sending', 'sent' => 'published',
            'cancelled' => 'archived',
            default => 'draft',
        };
        if ($status === 'published' && ! $publishedAt) {
            $publishedAt = now()->subDay();
        }

        return [$workflow, $status, $publishAt, $publishedAt];
    }

    private function eventData(array $item): array
    {
        $starts = isset($item['starts_in_days'])
            ? now()->addDays((int) $item['starts_in_days'])
            : (isset($item['starts_days_ago']) ? now()->subDays((int) $item['starts_days_ago']) : now()->addDays(7));
        if (isset($item['start_time'])) {
            [$h, $m] = array_pad(explode(':', $item['start_time']), 2, 0);
            $starts = $starts->setTime((int) $h, (int) $m);
        }

        return [
            'venue' => $item['venue'] ?? 'Khu vực dự án',
            'starts_at' => $starts,
            'duration_minutes' => $item['duration_minutes'] ?? 120,
            'capacity' => $item['capacity'] ?? null,
            'fee_amount' => $item['fee_amount'] ?? 0,
            'allow_guests' => $item['allow_guests'] ?? false,
            'max_guests' => $item['max_guests'] ?? 0,
            'qr_checkin' => $item['qr_checkin'] ?? false,
            'status' => match ($item['status'] ?? 'open_registration') {
                'completed' => 'finished', 'cancelled' => 'cancelled', default => 'upcoming',
            },
            'registration_status' => ($item['status'] ?? '') === 'full_waitlist' ? 'full' : 'open',
        ];
    }

    private function pollData(array $item): array
    {
        return [
            'question' => $item['question'] ?? $item['title'],
            'summary' => $item['summary'] ?? null,
            'options' => array_map(fn ($o) => ['key' => $o['key'] ?? null, 'label' => $o['label']], $item['options'] ?? []),
            'allow_multiple' => $item['allow_multiple'] ?? false,
            'max_choices' => $item['max_choices'] ?? null,
            'vote_scope' => $item['vote_scope'] ?? 'resident',
            'anonymous' => $item['anonymous'] ?? false,
            'allow_change_vote' => $item['allow_change_vote'] ?? false,
            'result_visibility' => $item['result_visibility'] ?? 'after_vote',
            'closes_at' => isset($item['closes_in_days']) ? now()->addDays((int) $item['closes_in_days'])
                : (isset($item['closed_days_ago']) ? now()->subDays((int) $item['closed_days_ago']) : null),
            'status' => match ($item['status'] ?? 'draft') {
                'open' => 'open', 'closed' => 'closed', 'scheduled' => 'draft', default => 'draft',
            },
        ];
    }

    /** Ghi vote_count đúng như seed (để kết quả poll hiển thị thật; aggregate khớp). */
    private function applyPollVotes(Notification $n, array $item): void
    {
        $poll = $n->contentPoll();
        if (! $poll) {
            return;
        }
        $total = 0;
        foreach (($item['options'] ?? []) as $opt) {
            $votes = (int) ($opt['votes'] ?? 0);
            $poll->options()->where('option_key', $opt['key'] ?? null)->update(['vote_count' => $votes]);
            $total += $votes;
        }
        $poll->forceFill(['vote_count' => $total])->save();
    }

    private function seedDeliverySamples(): void
    {
        $i = 0;
        foreach ($this->json('delivery_samples.json') as $s) {
            $n = Notification::withoutGlobalScopes()->where('tenant_id', $this->tenant->id)
                ->where('code', 'SEED:'.$s['campaign_seed_key'])->first();
            if (! $n) {
                continue;
            }
            $res = $this->residents[$i % $this->residents->count()];
            $i++;

            NotificationDeliveryLog::updateOrCreate(
                ['notification_id' => $n->id, 'user_id' => $res->user_id, 'channel' => $s['channel']],
                [
                    'resident_id' => $res->id,
                    'status' => $s['status'],
                    'error' => $s['error_code'] ?? null,
                    'sent_at' => now()->subMinutes(30),
                    'read_at' => isset($s['opened_minutes_after']) && $s['opened_minutes_after'] !== null
                        ? now()->subMinutes(30 - (int) $s['opened_minutes_after']) : null,
                ],
            );
        }
    }

    /** Remap rule seed (building_codes S1/S2… → mã tòa demo thật) + giữ chiều role/status. */
    private function remapRule(array $rules): array
    {
        $rule = ['scope' => [], 'include' => [], 'exclude' => []];

        if (! empty($rules['building_codes'])) {
            // Map N mã tòa seed → N tòa demo đầu tiên (giữ ý nghĩa "một số tòa").
            $n = count((array) $rules['building_codes']);
            $rule['scope']['building_ids'] = $this->buildings->take($n)->pluck('id')->all();
        }
        if (! empty($rules['relationship_roles'])) {
            // Map co_owner→owner, household_member→member (roles demo: owner|tenant|member).
            $roles = collect((array) $rules['relationship_roles'])->map(fn ($r) => match ($r) {
                'co_owner' => 'owner', 'household_member' => 'member', default => $r,
            })->unique()->values()->all();
            $rule['include'][] = ['field' => 'relationship_roles', 'operator' => 'in', 'value' => $roles];
        }
        if (! empty($rules['resident_status'])) {
            $rule['include'][] = ['field' => 'resident_status', 'operator' => 'in', 'value' => (array) $rules['resident_status']];
        }

        return $rule;
    }

    /** @return array<int,array<string,mixed>> */
    private function json(string $file): array
    {
        $path = $this->dir.'/'.$file;
        if (! is_file($path)) {
            throw new RuntimeException("Thiếu file seed: {$path}");
        }

        return json_decode(file_get_contents($path), true) ?? [];
    }
}
