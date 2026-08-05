<?php

declare(strict_types=1);

namespace App\Services\Resident\Interaction;

use App\Services\Resident\ResidentContextService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Trung tâm tương tác (handoff v1.1) — READ-MODEL HỢP NHẤT: gom phiếu đa nguồn của
 * CƯ DÂN về một danh sách chung (API contract §12). Adapter/union theo query (mỗi cư
 * dân ít phiếu) — chưa dùng projection table (ghi TECH_DEBT khi cần quy mô).
 *
 * Nguồn (audit `docs/INTERACTION_CENTER_AUDIT_20260804.md`): feedback · payment (xác nhận
 * TT) · visitor/amenity/binding (yêu cầu dịch vụ). Scope theo cư dân (apartment/resident/
 * user) — không lộ phiếu người khác (BOLA). KPI theo context, KHÔNG theo filter list.
 */
class InteractionAggregator
{
    public function __construct(private readonly ResidentContextService $context) {}

    private const FAMILY_LABEL = [
        'new' => 'Mới', 'in_progress' => 'Đang xử lý', 'waiting_resident' => 'Chờ cư dân',
        'done' => 'Hoàn tất', 'cancelled' => 'Đã hủy',
    ];

    /** map [source][status] => family. */
    private const STATUS_MAP = [
        'feedback' => ['new' => 'new', 'assigned' => 'in_progress', 'in_progress' => 'in_progress', 'resolved' => 'done', 'closed' => 'done'],
        'payment' => ['pending' => 'in_progress', 'confirmed' => 'done'],
        'visitor' => ['pending' => 'new', 'approved' => 'in_progress', 'checked_in' => 'in_progress', 'checked_out' => 'done', 'cancelled' => 'cancelled'],
        'amenity' => ['pending' => 'new', 'confirmed' => 'in_progress', 'completed' => 'done', 'cancelled' => 'cancelled', 'rejected' => 'cancelled'],
        'binding' => ['pending' => 'new', 'approved' => 'done', 'need_more_info' => 'waiting_resident', 'rejected' => 'cancelled', 'cancelled' => 'cancelled'],
    ];

    /** @return array{total_count:int,pending_count:int,near_sla_count:int} */
    public function summary(Authenticatable $user): array
    {
        $items = $this->all($user);

        return [
            'total_count' => count($items),
            'pending_count' => count(array_filter($items, fn ($i) => in_array($i['status_family'], ['new', 'in_progress', 'waiting_resident'], true))),
            'near_sla_count' => count(array_filter($items, fn ($i) => ($i['sla']['status'] ?? null) === 'near_due')),
        ];
    }

    /**
     * Danh sách đã lọc + sắp xếp + phân trang (cursor = offset đơn giản cho slice này).
     *
     * @param  array<string,mixed>  $f  q,type,subtype,status_family,sort,cursor,limit
     * @return array{items:array<int,array<string,mixed>>,next_cursor:?string}
     */
    public function list(Authenticatable $user, array $f): array
    {
        $items = $this->all($user);

        if (! empty($f['type'])) {
            $items = array_filter($items, fn ($i) => $i['type'] === $f['type']);
        }
        if (! empty($f['subtype'])) {
            $items = array_filter($items, fn ($i) => $i['subtype'] === $f['subtype']);
        }
        if (! empty($f['status_family']) && $f['status_family'] !== 'all') {
            $items = array_filter($items, fn ($i) => $i['status_family'] === $f['status_family']);
        }
        if (! empty($f['q'])) {
            $q = mb_strtolower((string) $f['q']);
            $items = array_filter($items, fn ($i) => str_contains(mb_strtolower($i['title'].' '.$i['summary'].' '.$i['ticket_no']), $q));
        }
        $items = array_values($items);

        // Sort: mặc định mới nhất; sla=gần hết; comments=nhiều bình luận.
        usort($items, match ($f['sort'] ?? 'latest') {
            'sla' => fn ($a, $b) => ($a['sla']['due_at'] ?? '9999') <=> ($b['sla']['due_at'] ?? '9999'),
            'comments' => fn ($a, $b) => $b['comment_count'] <=> $a['comment_count'],
            default => fn ($a, $b) => strcmp((string) $b['created_at'], (string) $a['created_at']),
        });

        $limit = max(1, min((int) ($f['limit'] ?? 20), 50));
        $offset = max(0, (int) ($f['cursor'] ?? 0));
        $page = array_slice($items, $offset, $limit);
        $next = ($offset + $limit) < count($items) ? (string) ($offset + $limit) : null;

        return ['items' => $page, 'next_cursor' => $next];
    }

    /** Toàn bộ item của cư dân (chưa lọc list). @return array<int,array<string,mixed>> */
    public function all(Authenticatable $user): array
    {
        [$apartmentIds, $residentIds, $userId] = $this->scope($user);
        if (empty($apartmentIds) && empty($residentIds)) {
            return [];
        }

        return array_merge(
            $this->feedback($apartmentIds, $residentIds, $userId),
            $this->payments($apartmentIds, $residentIds),
            $this->service('visitor', 'visitor_registrations', 'guest_registration', $apartmentIds, $residentIds),
            $this->service('amenity', 'amenity_bookings', 'amenity_booking', $apartmentIds, $residentIds),
            $this->service('binding', 'resident_binding_requests', 'vehicle_card_request', $apartmentIds, []),
        );
    }

    /** Nguồn hợp lệ cho chi tiết (khớp source_type ở list). */
    private const DETAIL_SOURCES = ['feedback', 'payment', 'visitor', 'amenity', 'binding'];

    /**
     * Chi tiết HỢP NHẤT 1 phiếu: item chuẩn hoá (như list) + mô tả đầy đủ + timeline
     * trao đổi (bình luận cư dân↔BQL). Dùng {@see all()} để đảm bảo CÙNG scope cư dân
     * (BOLA-safe) — phiếu ngoài phạm vi trả null → controller 404.
     *
     * @return array<string,mixed>|null
     */
    public function detail(Authenticatable $user, string $sourceType, string $sourceId): ?array
    {
        if (! in_array($sourceType, self::DETAIL_SOURCES, true)) {
            return null;
        }
        $item = null;
        foreach ($this->all($user) as $row) {
            if ($row['source_type'] === $sourceType && $row['source_id'] === (string) $sourceId) {
                $item = $row;
                break;
            }
        }
        if ($item === null) {
            return null;
        }

        $item['description'] = $this->description($sourceType, (int) $sourceId);
        $item['timeline'] = $this->timeline($sourceType, (int) $sourceId);

        return $item;
    }

    private function description(string $src, int $id): string
    {
        return match ($src) {
            'feedback' => (string) (DB::table('feedback_requests')->where('id', $id)->value('description') ?? ''),
            'payment' => (function () use ($id): string {
                $p = DB::table('payments')->where('id', $id)->first();
                if (! $p) {
                    return '';
                }
                $parts = ['Số tiền '.number_format((float) $p->amount).' đ'];
                if (! empty($p->reference_no)) {
                    $parts[] = 'Mã tham chiếu '.$p->reference_no;
                }
                if (! empty($p->note)) {
                    $parts[] = (string) $p->note;
                }

                return implode(' • ', $parts);
            })(),
            'visitor' => (string) (DB::table('visitor_registrations')->where('id', $id)->value('purpose') ?? ''),
            'amenity' => (string) (DB::table('amenity_bookings')->where('id', $id)->value('note') ?? ''),
            'binding' => (string) (DB::table('resident_binding_requests')->where('id', $id)->value('note') ?? ''),
            default => '',
        };
    }

    /**
     * Timeline = bình luận cư dân↔BQL, cũ→mới. Feedback dùng `feedback_comments`
     * (ẩn ghi chú nội bộ); visitor/payment/amenity dùng `comments` polymorphic.
     *
     * @return array<int,array<string,mixed>>
     */
    private function timeline(string $src, int $id): array
    {
        if ($src === 'feedback') {
            return DB::table('feedback_comments')->whereNull('deleted_at')
                ->where('feedback_request_id', $id)->where('is_internal', false)
                ->orderBy('created_at')->get()
                ->map(fn ($r) => [
                    'at' => (string) $r->created_at,
                    'author' => (string) ($r->author_name ?? ''),
                    'is_staff' => $r->resident_id === null,
                    'body' => (string) $r->body,
                ])->all();
        }

        $class = match ($src) {
            'visitor' => \App\Models\VisitorRegistration::class,
            'payment' => \App\Models\Payment::class,
            'amenity' => \App\Models\AmenityBooking::class,
            default => null,
        };
        if ($class === null) {
            return [];
        }

        return DB::table('comments')->whereNull('deleted_at')
            ->where('commentable_type', $class)->where('commentable_id', $id)
            ->orderBy('created_at')->get()
            ->map(fn ($r) => [
                'at' => (string) $r->created_at,
                'author' => (string) ($r->author_name ?? ''),
                'is_staff' => (bool) $r->is_staff,
                'body' => (string) $r->body,
            ])->all();
    }

    /** @return array{0:array<int>,1:array<int>,2:int} */
    private function scope(Authenticatable $user): array
    {
        $apartmentIds = $this->context->apartmentIds($user, request()->header('X-Context-Id'));
        $residentIds = method_exists($user, 'residentMemberships')
            ? $user->residentMemberships()->pluck('id')->map(fn ($v) => (int) $v)->all() : [];

        return [array_map('intval', $apartmentIds), $residentIds, (int) $user->id];
    }

    private function scoped(Builder $q, array $apartmentIds, array $residentIds, ?int $userId, ?string $userCol): Builder
    {
        return $q->where(function (Builder $w) use ($apartmentIds, $residentIds, $userId, $userCol) {
            if (! empty($apartmentIds)) {
                $w->orWhereIn('apartment_id', $apartmentIds);
            }
            if (! empty($residentIds)) {
                $w->orWhereIn('resident_id', $residentIds);
            }
            if ($userId !== null && $userCol !== null) {
                $w->orWhere($userCol, $userId);
            }
        });
    }

    private function fam(string $src, ?string $status): string
    {
        return self::STATUS_MAP[$src][$status] ?? 'in_progress';
    }

    private function item(array $x): array
    {
        $family = $x['status_family'];
        $terminal = in_array($family, ['done', 'cancelled'], true);

        return array_merge([
            'subtype' => null, 'summary' => '', 'sla' => null, 'comment_count' => 0,
            'unread_comment_count' => 0, 'attachment_count' => 0, 'thumbnail_url' => null, 'amount_vnd' => null,
            'status_label' => self::FAMILY_LABEL[$family] ?? $family,
            'capabilities' => [
                'view' => true, 'edit' => false, 'delete' => false,
                // Hủy: chỉ nguồn có endpoint resident-cancel thật (visitor/amenity); feedback/payment không.
                'cancel' => ! $terminal && in_array($x['source_type'] ?? '', ['visitor', 'amenity'], true),
                // Bình luận: mọi nguồn có endpoint comment (feedback + slip visitor/payment/amenity); trừ binding.
                'comment' => in_array($x['source_type'] ?? '', ['feedback', 'visitor', 'payment', 'amenity'], true),
                'supplement' => false,
                'rate' => $x['type'] === 'feedback' && $family === 'done',
                'reopen' => false,
            ],
        ], array_diff_key($x, ['can_cancel' => 0]));
    }

    /** @return array<int,array<string,mixed>> */
    private function feedback(array $apt, array $res, int $userId): array
    {
        $rows = $this->scoped(DB::table('feedback_requests')->whereNull('deleted_at'), $apt, $res, $userId, 'user_id')->get();
        $commentCounts = DB::table('feedback_comments')->whereNull('deleted_at')->where('is_internal', false)
            ->whereIn('feedback_request_id', $rows->pluck('id'))
            ->select('feedback_request_id', DB::raw('count(*) c'))->groupBy('feedback_request_id')->pluck('c', 'feedback_request_id');

        return $rows->map(fn ($r) => $this->item([
            'id' => 'int_feedback_'.$r->id, 'ticket_no' => $r->code, 'type' => 'feedback',
            'source_type' => 'feedback', 'source_id' => (string) $r->id,
            'title' => $r->title, 'summary' => mb_substr(strip_tags((string) $r->description), 0, 160),
            'status_family' => $this->fam('feedback', $r->status), 'status_code' => $r->status,
            'created_at' => (string) $r->created_at, 'last_activity_at' => (string) $r->updated_at,
            'sla' => $this->sla($r->sla_due_at, in_array($this->fam('feedback', $r->status), ['done', 'cancelled'], true)),
            'comment_count' => (int) ($commentCounts[$r->id] ?? 0),
        ]))->all();
    }

    /** @return array<int,array<string,mixed>> */
    private function payments(array $apt, array $res): array
    {
        return $this->scoped(DB::table('payments')->whereNull('deleted_at'), $apt, $res, null, null)->get()
            ->map(fn ($r) => $this->item([
                'id' => 'int_payment_'.$r->id, 'ticket_no' => $r->code, 'type' => 'payment_confirmation',
                'source_type' => 'payment', 'source_id' => (string) $r->id,
                'title' => 'Xác nhận thanh toán '.($r->reference_no ?: $r->code),
                'summary' => 'Số tiền '.number_format((float) $r->amount).' đ',
                'status_family' => $this->fam('payment', $r->status), 'status_code' => $r->status,
                'created_at' => (string) ($r->submitted_at ?? $r->created_at), 'last_activity_at' => (string) $r->updated_at,
                'amount_vnd' => (int) round((float) $r->amount),
            ]))->all();
    }

    /** @return array<int,array<string,mixed>> */
    private function service(string $src, string $table, string $subtype, array $apt, array $res): array
    {
        $rows = $this->scoped(DB::table($table)->whereNull('deleted_at'), $apt, $res, null, null)->get();

        return $rows->map(function ($r) use ($src, $subtype) {
            $title = match ($src) {
                'visitor' => 'Đăng ký khách: '.($r->visitor_name ?? $r->code),
                'amenity' => 'Đặt tiện ích '.$r->code,
                'binding' => 'Yêu cầu tài khoản/thẻ ('.($r->requested_role ?? '—').')',
                default => $r->code,
            };

            return $this->item([
                'id' => "int_{$src}_{$r->id}", 'ticket_no' => $r->code, 'type' => 'service_request', 'subtype' => $subtype,
                'source_type' => $src, 'source_id' => (string) $r->id,
                'title' => $title, 'summary' => (string) ($r->purpose ?? $r->note ?? ''),
                'status_family' => $this->fam($src, $r->status), 'status_code' => $r->status,
                'created_at' => (string) ($r->requested_at ?? $r->created_at), 'last_activity_at' => (string) $r->updated_at,
            ]);
        })->all();
    }

    /** @return array{status:string,due_at:?string,label:string}|null */
    private function sla(?string $dueAt, bool $terminal): ?array
    {
        if ($dueAt === null || $terminal) {
            return null;
        }
        $due = \Illuminate\Support\Carbon::parse($dueAt);
        $hours = now()->diffInHours($due, false);
        $status = $hours < 0 ? 'overdue' : ($hours <= 24 ? 'near_due' : 'on_track');
        // Nhãn hiển thị số giờ NGUYÊN (làm tròn) — tránh đuôi thập phân dài.
        $intHours = (int) round(abs($hours));
        $label = $hours < 0 ? 'Quá hạn SLA' : ($hours <= 24 ? "SLA còn {$intHours} giờ" : 'Trong hạn');

        return ['status' => $status, 'due_at' => $due->toIso8601String(), 'label' => $label];
    }
}
