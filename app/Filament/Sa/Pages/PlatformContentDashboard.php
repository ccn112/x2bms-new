<?php

namespace App\Filament\Sa\Pages;

use App\Filament\Concerns\PlatformScreen;
use App\Models\DocumentTemplate;
use App\Models\GlobalUserAccount;
use App\Models\KnowledgeDocument;
use App\Models\PlatformContent;
use App\Models\ResidentBindingRequest;
use BackedEnum;
use Filament\Pages\Page;

/**
 * WEB-UX-22-01 — Bảng điều khiển nội dung nền tảng.
 *
 * Control tower cho SuperAdmin: tổng hợp content, thư viện dùng chung, KB,
 * tài khoản chờ gắn căn và chỉ số AI indexing. Chỉ đọc + điều hướng nhanh
 * sang các màn xử lý (registry, binding queue, CMS, KB…). Mọi KPI tính từ DB.
 */
class PlatformContentDashboard extends Page
{
    use PlatformScreen;

    protected static function platformFeature(): ?string
    {
        return 'platform_content';
    }

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string|\UnitEnum|null $navigationGroup = 'Nền tảng (SuperAdmin)';

    protected static ?string $navigationLabel = 'Tổng quan nền tảng';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Tổng quan nội dung nền tảng';

    protected static ?string $slug = 'platform/dashboard';

    protected string $view = 'filament.pages.platform-content-dashboard';

    public const CONTENT_TYPE = [
        'news' => 'Tin tức', 'announcement' => 'Thông báo', 'banner' => 'Banner',
        'guide' => 'Hướng dẫn', 'public_project' => 'Dự án',
    ];

    protected function getViewData(): array
    {
        $failedIndex = KnowledgeDocument::where('ai_index_status', 'failed')->count();

        // Chart: content theo loại.
        $byType = PlatformContent::selectRaw('content_type, count(*) c')->groupBy('content_type')
            ->pluck('c', 'content_type');
        $contentByType = collect(self::CONTENT_TYPE)->map(fn ($label, $type) => [
            'label' => $label, 'count' => (int) ($byType[$type] ?? 0),
        ])->values()->filter(fn ($r) => $r['count'] > 0)->values();

        // Chart: KB theo phạm vi (owner_scope).
        $kbByScope = KnowledgeDocument::selectRaw('owner_scope, count(*) c')->groupBy('owner_scope')
            ->get()->map(fn ($r) => ['label' => ucfirst($r->owner_scope), 'count' => (int) $r->c])->values();

        // Chart: tài khoản mới theo tuần (8 tuần).
        $accounts = GlobalUserAccount::whereNotNull('first_registered_at')
            ->orderBy('first_registered_at')->pluck('first_registered_at');
        $weekBuckets = [];
        foreach ($accounts as $d) {
            $key = $d->copy()->startOfWeek()->format('d/m');
            $weekBuckets[$key] = ($weekBuckets[$key] ?? 0) + 1;
        }
        $newAccounts = collect($weekBuckets)->take(-8)->map(fn ($c, $k) => ['label' => $k, 'count' => $c])->values();

        return [
            'kpis' => [
                ['label' => 'Nội dung đã đăng', 'value' => PlatformContent::where('status', 'published')->count(), 'accent' => 'green'],
                ['label' => 'Chờ duyệt nội dung', 'value' => PlatformContent::where('status', 'pending_review')->count(), 'accent' => 'amber'],
                ['label' => 'Tài khoản gốc', 'value' => GlobalUserAccount::count(), 'accent' => 'blue'],
                ['label' => 'Chờ gắn căn', 'value' => ResidentBindingRequest::where('status', 'pending')->count(), 'accent' => 'amber'],
                ['label' => 'Mẫu tài liệu bật', 'value' => DocumentTemplate::where('status', 'active')->count(), 'accent' => 'blue'],
                ['label' => 'KB đã index AI', 'value' => KnowledgeDocument::where('ai_index_status', 'indexed')->count(), 'accent' => 'green'],
                ['label' => 'Index AI lỗi', 'value' => $failedIndex, 'accent' => $failedIndex > 0 ? 'red' : 'green'],
            ],
            'contentByType' => $contentByType,
            'kbByScope' => $kbByScope,
            'newAccounts' => $newAccounts,
            'pendingContents' => PlatformContent::where('status', 'pending_review')
                ->latest('updated_at')->limit(6)->get(),
            'pendingBindings' => ResidentBindingRequest::with(['account', 'apartment'])
                ->where('status', 'pending')->latest('requested_at')->limit(6)->get(),
            'failedDocs' => KnowledgeDocument::where('ai_index_status', 'failed')->latest('updated_at')->limit(6)->get(),
            'contentTypeLabels' => self::CONTENT_TYPE,
        ];
    }
}
