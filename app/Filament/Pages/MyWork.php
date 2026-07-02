<?php

namespace App\Filament\Pages;

use App\Models\ApprovalRequest;
use App\Models\AuditLog;
use App\Models\Building;
use App\Models\FeedbackRequest;
use App\Models\IocAlert;
use App\Models\PaymentRequest;
use App\Models\ResidentApprovalRequest;
use App\Models\SlaEvent;
use App\Models\Statement;
use App\Models\User;
use App\Models\WorkOrder;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

/**
 * BQL-00-06 — Việc của tôi & Chờ tôi duyệt (My Work & Approval Inbox).
 * A single inbox aggregating heterogeneous work across the project scope into tabs
 * (my tasks / awaiting my approval / system / SLA / recently handled), with priority
 * summary cards, left filter panel and per-row actions (open / approve / reject).
 * All rows are real records; approve/reject transition the source model + write audit.
 */
class MyWork extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-inbox-stack';

    protected static string|\UnitEnum|null $navigationGroup = 'Vận hành';

    protected static ?string $navigationLabel = 'Việc của tôi & Chờ tôi duyệt';

    protected static ?int $navigationSort = -5;

    protected static ?string $title = 'Việc của tôi & Chờ tôi duyệt';

    protected static ?string $slug = 'my-work';

    protected string $view = 'filament.pages.my-work';

    public string $tab = 'approve';

    public string $priority = 'all';

    public string $search = '';

    public string $typeFilter = 'all';

    public string $statusFilter = 'all';

    public const TABS = [
        'my' => 'Việc của tôi',
        'approve' => 'Chờ tôi duyệt',
        'system' => 'Thông báo hệ thống',
        'sla' => 'Cảnh báo SLA',
        'recent' => 'Đã xử lý gần đây',
    ];

    public const PRIORITY = [
        'urgent' => ['Khẩn cấp', 'red'],
        'high' => ['Cao', 'amber'],
        'normal' => ['Bình thường', 'blue'],
    ];

    /** @return array<int> */
    private function buildingIds(): array
    {
        return app(CurrentContext::class)->buildingIds() ?: [0];
    }

    private function projectName(): string
    {
        return app(CurrentContext::class)->project()?->name ?? '—';
    }

    /* ===================== Aggregation ===================== */

    /** Build the full (unfiltered) row set for a given tab. */
    private function rowsFor(string $tab): Collection
    {
        return match ($tab) {
            'approve' => $this->approvalRows(),
            'my' => $this->myTaskRows(),
            'sla' => $this->slaRows(),
            'system' => $this->systemRows(),
            'recent' => $this->recentRows(),
            default => collect(),
        };
    }

    private function buildingName(?int $id): string
    {
        return $id ? (Building::find($id)?->name ?? '—') : '—';
    }

    private function approvalRows(): Collection
    {
        $bids = $this->buildingIds();
        $rows = collect();

        ApprovalRequest::query()->whereIn('status', ['pending', 'reviewing'])
            ->when(app(CurrentContext::class)->projectId(), fn ($q, $p) => $q->where('project_id', $p))
            ->with('requester')->latest()->limit(50)->get()
            ->each(fn (ApprovalRequest $a) => $rows->push([
                'key' => 'approval:'.$a->id, 'type' => 'approval', 'icon' => 'doc',
                'title' => $a->title, 'code' => $a->code,
                'building' => $this->buildingName($a->building_id),
                'priority' => ($a->amount ?? 0) >= 50_000_000 ? 'urgent' : 'high',
                'due_at' => $a->decided_at, 'status' => ['Chờ duyệt', 'amber'],
                'by' => [$a->requester?->name ?? 'Hệ thống', 'Người yêu cầu'],
                'actions' => ['open', 'approve', 'reject'],
            ]));

        Statement::query()->whereIn('building_id', $bids)->where('approval_status', 'pending')
            ->latest()->limit(50)->get()
            ->each(fn (Statement $s) => $rows->push([
                'key' => 'statement:'.$s->id, 'type' => 'statement', 'icon' => 'statement',
                'title' => 'Duyệt bảng kê '.$s->code, 'code' => $s->code,
                'building' => $this->buildingName($s->building_id),
                'priority' => ($s->total_amount ?? 0) >= 50_000_000 ? 'urgent' : 'high',
                'due_at' => null, 'status' => ['Chờ duyệt', 'amber'],
                'by' => ['Kế toán', 'Tài chính'],
                'actions' => ['open', 'approve', 'reject'],
            ]));

        ResidentApprovalRequest::query()->whereIn('building_id', $bids)->where('status', 'pending')
            ->latest()->limit(50)->get()
            ->each(fn (ResidentApprovalRequest $r) => $rows->push([
                'key' => 'resident:'.$r->id, 'type' => 'resident', 'icon' => 'user',
                'title' => 'Duyệt đăng ký cư dân: '.$r->full_name, 'code' => '#'.$r->id,
                'building' => $this->buildingName($r->building_id),
                'priority' => 'high', 'due_at' => null, 'status' => ['Chờ duyệt', 'amber'],
                'by' => ['QL Cư dân', 'Cư dân & Căn hộ'],
                'actions' => ['open', 'approve', 'reject'],
            ]));

        PaymentRequest::query()->whereIn('status', ['pending', 'submitted'])
            ->when(app(CurrentContext::class)->projectId(), fn ($q, $p) => $q->where('project_id', $p))
            ->with('requester')->latest()->limit(50)->get()
            ->each(fn (PaymentRequest $p) => $rows->push([
                'key' => 'payment:'.$p->id, 'type' => 'payment', 'icon' => 'cash',
                'title' => 'Duyệt đề nghị thanh toán: '.$p->title, 'code' => $p->code,
                'building' => '—',
                'priority' => ($p->amount ?? 0) >= 50_000_000 ? 'urgent' : 'high',
                'due_at' => $p->due_date, 'status' => ['Chờ duyệt', 'amber'],
                'by' => [$p->requester?->name ?? 'Kế toán', 'Tài chính'],
                'actions' => ['open', 'approve', 'reject'],
            ]));

        return $rows;
    }

    private function myTaskRows(): Collection
    {
        $bids = $this->buildingIds();
        $uid = auth()->id();
        $rows = collect();

        WorkOrder::query()->whereIn('building_id', $bids)
            ->whereIn('status', ['pending', 'in_progress'])
            ->where(fn ($q) => $q->where('assigned_to_id', $uid)->orWhere('created_by_id', $uid)->orWhereNull('assigned_to_id'))
            ->with(['building', 'creator'])->orderBy('due_at')->limit(60)->get()
            ->each(fn (WorkOrder $w) => $rows->push([
                'key' => 'workorder:'.$w->id, 'type' => 'workorder', 'icon' => 'clipboard',
                'title' => $w->title, 'code' => $w->code,
                'building' => $w->building?->name ?? '—',
                'priority' => $this->mapPriority($this->val($w->priority)),
                'due_at' => $w->due_at, 'status' => $this->woStatus($this->val($w->status)),
                'by' => [$w->creator?->name ?? 'Hệ thống', 'Người giao'],
                'actions' => ['open'],
            ]));

        FeedbackRequest::query()->whereIn('building_id', $bids)
            ->whereIn('status', ['new', 'assigned', 'in_progress', 'processing'])
            ->where(fn ($q) => $q->where('assigned_to_id', $uid)->orWhereNull('assigned_to_id'))
            ->with('building')->orderBy('sla_due_at')->limit(60)->get()
            ->each(fn (FeedbackRequest $f) => $rows->push([
                'key' => 'feedback:'.$f->id, 'type' => 'feedback', 'icon' => 'chat',
                'title' => $f->title, 'code' => $f->code,
                'building' => $f->building?->name ?? '—',
                'priority' => $this->mapPriority($this->val($f->priority)),
                'due_at' => $f->sla_due_at, 'status' => ['Chờ trả lời', 'blue'],
                'by' => ['Cư dân', 'Phản ánh'],
                'actions' => ['open'],
            ]));

        return $rows;
    }

    private function slaRows(): Collection
    {
        $bids = $this->buildingIds();
        $rows = collect();

        SlaEvent::query()->whereIn('building_id', $bids)->where('status', 'open')
            ->latest()->limit(60)->get()
            ->each(fn (SlaEvent $e) => $rows->push([
                'key' => 'sla:'.$e->id, 'type' => 'sla', 'icon' => 'alert',
                'title' => $e->description ?: 'Cảnh báo SLA', 'code' => '#SLA-'.$e->id,
                'building' => $this->buildingName($e->building_id),
                'priority' => 'urgent', 'due_at' => null, 'status' => ['SLA trễ', 'red'],
                'by' => ['Hệ thống', 'SLA'],
                'actions' => ['open'],
            ]));

        FeedbackRequest::query()->whereIn('building_id', $bids)
            ->whereNotIn('status', ['resolved', 'closed'])
            ->whereNotNull('sla_due_at')->where('sla_due_at', '<', now())
            ->with('building')->orderBy('sla_due_at')->limit(60)->get()
            ->each(fn (FeedbackRequest $f) => $rows->push([
                'key' => 'feedback:'.$f->id, 'type' => 'feedback', 'icon' => 'alert',
                'title' => 'Quá hạn SLA: '.$f->title, 'code' => $f->code,
                'building' => $f->building?->name ?? '—',
                'priority' => 'urgent', 'due_at' => $f->sla_due_at, 'status' => ['SLA trễ', 'red'],
                'by' => ['Cư dân', 'Phản ánh'],
                'actions' => ['open'],
            ]));

        return $rows;
    }

    private function systemRows(): Collection
    {
        $bids = $this->buildingIds();

        return IocAlert::query()->whereIn('building_id', $bids)->where('status', 'open')
            ->latest()->limit(60)->get()
            ->map(fn (IocAlert $a) => [
                'key' => 'ioc:'.$a->id, 'type' => 'system', 'icon' => 'alert',
                'title' => $a->title, 'code' => '#SYS-'.$a->id,
                'building' => $this->buildingName($a->building_id),
                'priority' => $a->severity === 'critical' ? 'urgent' : ($a->severity === 'warning' ? 'high' : 'normal'),
                'due_at' => null, 'status' => ['Thông báo', 'blue'],
                'by' => ['Hệ thống', 'Giám sát'],
                'actions' => ['open'],
            ]);
    }

    private function recentRows(): Collection
    {
        return AuditLog::query()
            ->when(app(CurrentContext::class)->tenantId(), fn ($q, $t) => $q->where('tenant_id', $t))
            ->where('user_id', auth()->id())->latest()->limit(40)->get()
            ->map(fn (AuditLog $a) => [
                'key' => 'audit:'.$a->id, 'type' => 'audit', 'icon' => 'check',
                'title' => $a->description ?: $a->action, 'code' => $a->action,
                'building' => $this->buildingName($a->building_id),
                'priority' => 'normal', 'due_at' => $a->created_at, 'status' => ['Đã xử lý', 'green'],
                'by' => [$a->actor_name ?: 'Bạn', 'Bạn'],
                'actions' => [],
            ]);
    }

    /** Normalise a value that may be a BackedEnum (Filament casts) to its scalar. */
    private function val(mixed $x): ?string
    {
        if ($x instanceof \BackedEnum) {
            return (string) $x->value;
        }

        return $x === null ? null : (string) $x;
    }

    private function mapPriority(?string $p): string
    {
        return match ($p) {
            'urgent', 'critical' => 'urgent',
            'high' => 'high',
            default => 'normal',
        };
    }

    private function woStatus(?string $s): array
    {
        return match ($s) {
            'in_progress' => ['Đang xử lý', 'blue'],
            'pending' => ['Chờ xử lý', 'amber'],
            default => [$s ?? '—', 'slate'],
        };
    }

    /* ===================== View data ===================== */

    protected function getViewData(): array
    {
        $all = $this->rowsFor($this->tab);

        // Priority summary (from the tab's full set, before list filters).
        $prioCounts = [
            'urgent' => $all->where('priority', 'urgent')->count(),
            'high' => $all->where('priority', 'high')->count(),
            'normal' => $all->where('priority', 'normal')->count(),
        ];

        // Apply list filters.
        $rows = $all
            ->when($this->priority !== 'all', fn (Collection $c) => $c->where('priority', $this->priority))
            ->when($this->typeFilter !== 'all', fn (Collection $c) => $c->where('type', $this->typeFilter))
            ->when($this->search !== '', fn (Collection $c) => $c->filter(fn ($r) => str_contains(mb_strtolower($r['title'].' '.$r['code']), mb_strtolower($this->search))))
            ->values();

        return [
            'tabCounts' => collect(self::TABS)->map(fn ($_, $k) => $this->rowsFor($k)->count())->all(),
            'prioCounts' => $prioCounts,
            'rows' => $rows,
            'projectName' => $this->projectName(),
            'typeOptions' => $all->pluck('type')->unique()->values()->all(),
        ];
    }

    /* ===================== Actions ===================== */

    public function setTab(string $tab): void
    {
        $this->tab = array_key_exists($tab, self::TABS) ? $tab : 'approve';
        $this->priority = 'all';
    }

    public function resetFilters(): void
    {
        $this->priority = 'all';
        $this->typeFilter = 'all';
        $this->statusFilter = 'all';
        $this->search = '';
    }

    public function decide(string $key, string $decision): void
    {
        [$type, $id] = explode(':', $key);
        $isApprove = $decision === 'approve';

        $ok = match ($type) {
            'approval' => (bool) ApprovalRequest::whereKey($id)->update(['status' => $isApprove ? 'approved' : 'rejected', 'decided_at' => now()]),
            'statement' => (bool) Statement::whereKey($id)->update(['approval_status' => $isApprove ? 'approved' : 'rejected']),
            'resident' => (bool) ResidentApprovalRequest::whereKey($id)->update(['status' => $isApprove ? 'approved' : 'rejected']),
            'payment' => (bool) PaymentRequest::whereKey($id)->update(['status' => $isApprove ? 'approved' : 'rejected']),
            default => false,
        };

        if (! $ok) {
            Notification::make()->title('Không thể xử lý mục này')->danger()->send();

            return;
        }

        $verb = $isApprove ? 'Phê duyệt' : 'Từ chối';
        $user = auth()->user();
        AuditLog::create([
            'tenant_id' => $user->tenant_id,
            'building_id' => $user->building_id,
            'user_id' => $user->id,
            'actor_name' => $user->name,
            'action' => 'inbox.'.$decision,
            'subject_type' => $type,
            'subject_id' => (int) $id,
            'description' => $verb.' mục '.$key.' từ hộp thư công việc',
        ]);

        Notification::make()->title($verb.' thành công')->{$isApprove ? 'success' : 'warning'}()->send();
    }
}
