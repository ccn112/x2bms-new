<?php

namespace App\Livewire;

use App\Models\Apartment;
use App\Models\FeedbackRequest;
use App\Models\Resident;
use App\Models\WorkOrder;
use App\Support\Context\CurrentContext;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * WEB-UX-10 — Global search / command palette. Single shared search surface opened from
 * the desktop header search button and the mobile header search, plus Ctrl/Cmd+K.
 * Queries residents / apartments / feedback / work orders scoped to the current project's
 * buildings. (The /admin panel is Pages-only, so this replaces Filament's resource-based
 * global search with an equivalent context-aware command palette.)
 */
class GlobalSearch extends Component
{
    public bool $open = false;

    public string $q = '';

    /** Quick navigation shortcuts (WEB-UX-10). */
    public function quickNav(): array
    {
        return [
            ['label' => 'Đi đến danh sách cư dân', 'sub' => 'Xem và quản lý cư dân', 'icon' => 'users', 'url' => url('/admin/residents')],
            ['label' => 'Tạo phản ánh mới', 'sub' => 'Ghi nhận phản ánh từ cư dân', 'icon' => 'chat', 'url' => url('/admin/feedback/queue')],
            ['label' => 'Duyệt bảng kê phí', 'sub' => 'Xem bảng kê chờ duyệt', 'icon' => 'doc', 'url' => url('/admin/finance/statement-approvals')],
            ['label' => 'Xem công việc hôm nay', 'sub' => 'Danh sách công việc cần xử lý', 'icon' => 'clipboard', 'url' => url('/admin/work-orders/kanban')],
        ];
    }

    /** @return array<int,string> */
    public function recent(): array
    {
        return array_values(array_filter((array) session('x2_recent_search', [])));
    }

    #[On('open-x2-search')]
    public function openSearch(): void
    {
        $this->open = true;
    }

    public function closeSearch(): void
    {
        $this->open = false;
        $this->q = '';
    }

    public function clearRecent(): void
    {
        session()->forget('x2_recent_search');
    }

    private function remember(string $term): void
    {
        $term = trim($term);
        if ($term === '') {
            return;
        }
        $recent = array_values(array_filter((array) session('x2_recent_search', []), fn ($r) => $r !== $term));
        array_unshift($recent, $term);
        session(['x2_recent_search' => array_slice($recent, 0, 6)]);
    }

    public function useRecent(string $term): void
    {
        $this->q = $term;
    }

    /** Grouped results scoped to the current context. */
    public function getResultsProperty(): array
    {
        $q = trim($this->q);
        if (mb_strlen($q) < 2) {
            return [];
        }

        $this->remember($q);
        $ctx = app(CurrentContext::class);
        $bids = $ctx->buildingIds() ?: [0];
        $like = '%'.$q.'%';
        $groups = [];

        $residents = Resident::query()->whereIn('building_id', $bids)
            ->where(fn ($w) => $w->where('full_name', 'like', $like)->orWhere('phone', 'like', $like)->orWhere('code', 'like', $like)->orWhere('id_no', 'like', $like))
            ->limit(4)->get();
        if ($residents->isNotEmpty()) {
            $groups[] = ['label' => 'Cư dân', 'badge' => 'Cư dân', 'all' => url('/admin/residents'), 'icon' => 'user',
                'items' => $residents->map(fn (Resident $r) => ['title' => $r->full_name, 'sub' => trim(($r->code ? $r->code.' · ' : '').($r->phone ?? '')), 'url' => url('/admin/residents/'.$r->id.'/detail')])->all()];
        }

        $apartments = Apartment::query()->whereIn('building_id', $bids)->where('code', 'like', $like)
            ->with('building')->limit(4)->get();
        if ($apartments->isNotEmpty()) {
            $groups[] = ['label' => 'Căn hộ', 'badge' => 'Căn hộ', 'all' => url('/admin/apartments'), 'icon' => 'home',
                'items' => $apartments->map(fn (Apartment $a) => ['title' => $a->code, 'sub' => $a->building?->name, 'url' => url('/admin/apartments/'.$a->id.'/profile')])->all()];
        }

        $feedback = FeedbackRequest::query()->whereIn('building_id', $bids)
            ->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('code', 'like', $like))->limit(4)->get();
        if ($feedback->isNotEmpty()) {
            $groups[] = ['label' => 'Phản ánh', 'badge' => 'Phản ánh', 'all' => url('/admin/feedback/queue'), 'icon' => 'chat',
                'items' => $feedback->map(fn (FeedbackRequest $f) => ['title' => $f->title, 'sub' => $f->code, 'url' => url('/admin/feedback/queue')])->all()];
        }

        $workOrders = WorkOrder::query()->whereIn('building_id', $bids)
            ->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('code', 'like', $like))->limit(4)->get();
        if ($workOrders->isNotEmpty()) {
            $groups[] = ['label' => 'Công việc', 'badge' => 'Công việc', 'all' => url('/admin/work-orders/kanban'), 'icon' => 'clipboard',
                'items' => $workOrders->map(fn (WorkOrder $w) => ['title' => $w->title, 'sub' => $w->code, 'url' => url('/admin/work-orders/kanban')])->all()];
        }

        return $groups;
    }

    public function render()
    {
        return view('livewire.global-search');
    }
}
