<?php

namespace App\Filament\Sa\Pages;

use App\Filament\Concerns\PlatformScreen;
use App\Filament\Concerns\WritesAudit;
use App\Models\Project;
use App\Models\PublicProject;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Nối **dự án vận hành** với **bản ghi danh mục công khai**.
 *
 * ## Vì sao cần màn này
 *
 * "Dự án" đang tồn tại ở hai bảng không nối với nhau:
 *
 * - `projects` — dự án vận hành: có tenant, toà, căn hộ, cư dân.
 * - `public_projects` — danh mục công khai (~6.000 dòng, nguồn batdongsan): thứ khách
 *   xem khi chưa đăng nhập và chọn "quan tâm" lúc đăng ký.
 *
 * Không có khoá nối thì **"khách quan tâm Sunshine Garden" và "cư dân Sunshine Garden"
 * là hai chữ Sunshine Garden khác nhau**. Khách mua nhà xong, trở thành cư dân, mà hệ
 * thống không biết đó là cùng một dự án — bậc thang cộng đồng đứt ở nấc giữa, và
 * `user_project_follows` không backfill được.
 *
 * ## Vì sao nối tay chứ không tự động
 *
 * Khớp theo tên chính xác chỉ được 5/27 dự án. Khớp mờ thì nguy hiểm: "Sunshine Garden"
 * có ở nhiều tỉnh, nối nhầm là gắn cư dân dự án này vào danh mục dự án khác — và
 * **không ai phát hiện ra** vì mọi thứ trông vẫn bình thường.
 *
 * Chủ dự án chốt 2026-07-29: **SuperAdmin nối**, BQL phân quyền sau.
 */
class ProjectCatalogLinking extends Page implements HasTable
{
    use InteractsWithTable;
    use PlatformScreen;
    use WritesAudit;

    /** Chỉ SuperAdmin — đây là việc đối chiếu dữ liệu nền tảng. */
    protected static function platformFeature(): ?string
    {
        return null;
    }

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-link';

    protected static string|\UnitEnum|null $navigationGroup = 'Nền tảng (SuperAdmin)';

    protected static ?string $navigationLabel = 'Nối dự án ↔ danh mục';

    protected static ?int $navigationSort = 32;

    protected static ?string $title = 'Nối dự án vận hành với danh mục công khai';

    protected static ?string $slug = 'platform/project-catalog-linking';

    protected string $view = 'filament.pages.project-catalog-linking';

    /** Số dự án chưa nối — hiện trên badge điều hướng để không ai quên việc này. */
    public static function getNavigationBadge(): ?string
    {
        $pending = Project::withoutGlobalScopes()->whereNull('public_project_id')->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Project::withoutGlobalScopes()
                    ->with(['publicProject:id,code,name,province,district'])
            )
            ->defaultSort('public_project_id') // chưa nối (null) lên đầu
            ->columns([
                TextColumn::make('name')
                    ->label('Dự án vận hành')
                    ->description(fn (Project $r) => trim(collect([
                        $r->code,
                        $r->district,
                        $r->city,
                    ])->filter()->implode(' · ')) ?: null)
                    ->searchable(['name', 'code'])
                    ->wrap(),

                TextColumn::make('tenant.name')
                    ->label('Công ty')
                    ->toggleable()
                    ->searchable(),

                TextColumn::make('publicProject.name')
                    ->label('Bản ghi danh mục')
                    ->description(fn (Project $r) => $r->publicProject
                        ? trim(collect([
                            $r->publicProject->code,
                            $r->publicProject->district,
                            $r->publicProject->province,
                        ])->filter()->implode(' · '))
                        : null)
                    ->placeholder('— chưa nối —')
                    ->badge(fn (Project $r) => $r->public_project_id === null)
                    ->color(fn (Project $r) => $r->public_project_id === null ? 'warning' : 'success')
                    ->wrap(),
            ])
            ->filters([
                TernaryFilter::make('linked')
                    ->label('Trạng thái nối')
                    ->placeholder('Tất cả')
                    ->trueLabel('Đã nối')
                    ->falseLabel('Chưa nối')
                    ->queries(
                        true: fn (Builder $q) => $q->whereNotNull('public_project_id'),
                        false: fn (Builder $q) => $q->whereNull('public_project_id'),
                    ),
            ])
            ->recordActions([
                Action::make('link')
                    ->label(fn (Project $r) => $r->public_project_id ? 'Đổi liên kết' : 'Nối')
                    ->icon('heroicon-o-link')
                    ->color(fn (Project $r) => $r->public_project_id ? 'gray' : 'primary')
                    ->schema([
                        Select::make('public_project_id')
                            ->label('Bản ghi trong danh mục công khai')
                            ->helperText('Gõ tên dự án để tìm. Đối chiếu cả tỉnh/quận trước khi chọn — '
                                .'nhiều dự án trùng tên ở các tỉnh khác nhau.')
                            ->searchable()
                            ->required()
                            // Tìm theo tên VÀ mã, kèm địa danh trong nhãn: chỉ có tên thì
                            // không phân biệt được hai "Sunshine Garden" ở hai tỉnh.
                            ->getSearchResultsUsing(fn (string $search) => PublicProject::query()
                                ->where(fn ($q) => $q
                                    ->where('name', 'like', "%{$search}%")
                                    ->orWhere('code', 'like', "%{$search}%"))
                                ->limit(40)
                                ->get()
                                ->mapWithKeys(fn ($p) => [$p->id => self::catalogLabel($p)])
                                ->all())
                            ->getOptionLabelUsing(fn ($value) => ($p = PublicProject::find($value))
                                ? self::catalogLabel($p)
                                : null),
                    ])
                    ->action(function (Project $record, array $data) {
                        $before = $record->public_project_id;
                        $record->forceFill(['public_project_id' => $data['public_project_id']])
                            ->saveQuietly();

                        $this->audit('project.catalog_link',
                            "Nối dự án #{$record->id} với danh mục #{$data['public_project_id']}"
                                .($before ? " (trước: #{$before})" : ''),
                            Project::class, $record->id);

                        Notification::make()
                            ->success()
                            ->title('Đã nối dự án')
                            ->body($record->name.' → '.self::catalogLabel($record->publicProject()->first()))
                            ->send();
                    }),

                Action::make('unlink')
                    ->label('Gỡ liên kết')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (Project $r) => $r->public_project_id !== null)
                    ->requiresConfirmation()
                    ->modalHeading('Gỡ liên kết danh mục?')
                    ->modalDescription('Người đang theo dõi dự án qua danh mục sẽ không còn được '
                        .'ưu tiên hiển thị nội dung của dự án này.')
                    ->action(function (Project $record) {
                        $before = $record->public_project_id;
                        $record->forceFill(['public_project_id' => null])->saveQuietly();

                        $this->audit('project.catalog_unlink',
                            "Gỡ liên kết danh mục #{$before} khỏi dự án #{$record->id}",
                            Project::class, $record->id);

                        Notification::make()->warning()->title('Đã gỡ liên kết')->send();
                    }),
            ])
            ->emptyStateHeading('Chưa có dự án vận hành nào');
    }

    /** Nhãn danh mục LUÔN kèm địa danh — tên không thôi là nối nhầm. */
    private static function catalogLabel(?PublicProject $p): string
    {
        if ($p === null) {
            return '—';
        }

        $where = collect([$p->district, $p->province])->filter()->implode(', ');

        return $p->name.($where !== '' ? " ({$where})" : '')." · {$p->code}";
    }

    /** Số liệu hiển thị trên đầu trang. */
    public function getStats(): array
    {
        $total = Project::withoutGlobalScopes()->count();
        $linked = Project::withoutGlobalScopes()->whereNotNull('public_project_id')->count();

        return [
            'total' => $total,
            'linked' => $linked,
            'pending' => $total - $linked,
            'actor' => Auth::user()?->name,
        ];
    }
}
