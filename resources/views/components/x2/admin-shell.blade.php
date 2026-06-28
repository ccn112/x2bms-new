@props([
    'active' => null,      // nav key to highlight
    'breadcrumb' => null,
])

@php
    $user = auth()->user();
    $building = $user?->building_id
        ? \App\Models\Building::find($user->building_id)
        : \App\Models\Building::first();

    $pendingFeedback = \App\Models\FeedbackRequest::whereIn('status', \App\Enums\FeedbackStatus::pendingValues())->count();
    $openAlerts = \App\Models\SlaEvent::where('status', 'open')->count();
    $pendingApprovals = \App\Models\ResidentApprovalRequest::where('status', 'pending')->count();
    $lastAudit = \App\Models\AuditLog::latest()->first();

    $nav = [
        ['label' => null, 'items' => [
            ['label' => 'Tổng quan', 'route' => url('/dashboard'), 'active' => $active === 'dashboard'],
        ]],
        ['label' => 'CƯ DÂN & CĂN HỘ', 'items' => [
            ['label' => 'Cư dân', 'route' => url('/residents'), 'active' => $active === 'residents'],
            ['label' => 'Hồ sơ căn hộ', 'route' => url('/apartments'), 'active' => $active === 'apartments'],
            ['label' => 'Phương tiện & thẻ', 'route' => url('/vehicles-cards'), 'active' => $active === 'vehicles'],
            ['label' => 'Duyệt cư dân', 'route' => url('/resident-approvals'), 'active' => $active === 'approvals', 'badge' => $pendingApprovals ?: null],
        ]],
        ['label' => 'VẬN HÀNH', 'items' => [
            ['label' => 'Phản ánh & đánh giá', 'route' => '#', 'badge' => $pendingFeedback ?: null],
            ['label' => 'Cảnh báo & IOC', 'route' => '#', 'badge' => $openAlerts ?: null],
        ]],
        ['label' => 'TÀI CHÍNH', 'items' => [
            ['label' => 'Kỳ phí & bảng kê', 'route' => '#'],
            ['label' => 'Công nợ & thanh toán', 'route' => '#'],
        ]],
    ];
@endphp

<x-x2.page-shell>
    <x-slot:sidebar>
        <x-x2.sidebar brand="X2-BMS" tagline="Operation Center" :groups="$nav" />
    </x-slot:sidebar>

    <x-slot:topbar>
        <x-x2.topbar
            :breadcrumb="$breadcrumb"
            :buildingName="$building?->name"
            :userName="$user?->name"
            :userRole="$user?->title"
            :notificationCount="$openAlerts" />
    </x-slot:topbar>

    <x-slot:footer>
        <x-x2.audit-footer
            :lastActor="$lastAudit?->actor_name"
            :lastAction="$lastAudit?->description"
            :lastAt="$lastAudit?->created_at?->format('d/m/Y H:i')"
            version="v0.2-web02" />
    </x-slot:footer>

    {{ $slot }}
</x-x2.page-shell>
