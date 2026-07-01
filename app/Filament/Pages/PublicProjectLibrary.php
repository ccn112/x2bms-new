<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\PlatformScreen;
use App\Filament\Concerns\WritesAudit;
use App\Models\ProjectMedia;
use App\Models\PublicProject;
use App\Models\Tenant;
use App\Models\TenantProjectLink;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

/**
 * WEB-UX-22-03 — Thư viện dự án public dùng chung.
 *
 * Thông tin dự án/tòa master cho trang public, app onboarding và setup tenant.
 * Platform sở hữu master; tenant có thể LINK (không sửa master). Media có thứ tự.
 */
class PublicProjectLibrary extends Page implements HasTable
{
    use InteractsWithTable;
    use PlatformScreen;
    use WritesAudit;

    protected static function platformFeature(): ?string
    {
        return 'public_project';
    }

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|\UnitEnum|null $navigationGroup = 'Nền tảng (SuperAdmin)';

    protected static ?string $navigationLabel = 'Thư viện dự án';

    protected static ?int $navigationSort = 31;

    protected static ?string $title = 'Thư viện dự án public';

    protected static ?string $slug = 'platform/public-projects';

    protected string $view = 'filament.pages.public-project-library';

    public const STATUS = [
        'planning' => ['Quy hoạch', 'gray'],
        'selling' => ['Đang bán', 'info'],
        'handover' => ['Bàn giao', 'warning'],
        'operating' => ['Vận hành', 'success'],
        'archived' => ['Lưu trữ', 'gray'],
    ];

    protected function getViewData(): array
    {
        return [
            'kpis' => [
                ['label' => 'Tổng dự án', 'value' => PublicProject::count(), 'accent' => 'blue'],
                ['label' => 'Đang vận hành', 'value' => PublicProject::where('status', 'operating')->count(), 'accent' => 'green'],
                ['label' => 'Công khai', 'value' => PublicProject::where('is_public', true)->count(), 'accent' => 'blue'],
                ['label' => 'Đã liên kết tenant', 'value' => TenantProjectLink::withoutGlobalScope('tenant')->whereNotNull('public_project_id')->distinct('public_project_id')->count('public_project_id'), 'accent' => 'amber'],
            ],
        ];
    }

    /** @return array<\Filament\Forms\Components\Component> */
    private function formSchema(): array
    {
        return [
            TextInput::make('code')->label('Mã')->required()->maxLength(50),
            TextInput::make('name')->label('Tên dự án')->required()->maxLength(255),
            TextInput::make('developer_name')->label('Chủ đầu tư')->maxLength(255),
            Select::make('project_type')->label('Loại hình')
                ->options(['apartment' => 'Chung cư', 'villa' => 'Biệt thự', 'mixed' => 'Phức hợp', 'office' => 'Văn phòng']),
            TextInput::make('province')->label('Tỉnh/TP')->maxLength(100),
            TextInput::make('address')->label('Địa chỉ')->maxLength(255)->columnSpanFull(),
            Select::make('status')->label('Trạng thái')->options(collect(self::STATUS)->map(fn ($v) => $v[0])->all())->default('operating'),
            TextInput::make('blocks')->label('Số block/tòa')->numeric()->default(0),
            TextInput::make('apartments')->label('Số căn hộ')->numeric()->default(0),
            Textarea::make('description')->label('Mô tả')->rows(3)->columnSpanFull(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(PublicProject::query()->withCount('media'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label('Dự án')->searchable()->weight('medium')
                    ->description(fn (PublicProject $p) => $p->developer_name),
                TextColumn::make('province')->label('Địa điểm')->placeholder('—'),
                TextColumn::make('project_type')->label('Loại')->badge()->color('gray')->placeholder('—')->toggleable(),
                TextColumn::make('blocks')->label('Block')->alignCenter(),
                TextColumn::make('apartments')->label('Căn hộ')->alignCenter()->numeric(),
                TextColumn::make('media_count')->label('Media')->badge()->color('info')->alignCenter(),
                TextColumn::make('status')->label('Trạng thái')->badge()
                    ->formatStateUsing(fn (string $state) => self::STATUS[$state][0] ?? $state)
                    ->color(fn (string $state) => self::STATUS[$state][1] ?? 'gray'),
                TextColumn::make('is_public')->label('Public')->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Có' : 'Ẩn')->color(fn ($state) => $state ? 'success' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('status')->label('Trạng thái')->options(collect(self::STATUS)->map(fn ($v) => $v[0])->all()),
                SelectFilter::make('project_type')->label('Loại hình')
                    ->options(['apartment' => 'Chung cư', 'villa' => 'Biệt thự', 'mixed' => 'Phức hợp', 'office' => 'Văn phòng']),
            ])
            ->headerActions([
                Action::make('create')->label('Thêm dự án')->icon('heroicon-m-plus')->color('primary')
                    ->schema($this->formSchema())
                    ->action(function (array $data): void {
                        $p = PublicProject::create($data);
                        $this->audit('public_project.create', 'Tạo dự án: '.$p->name, PublicProject::class, $p->id);
                        Notification::make()->title('Đã thêm dự án')->success()->send();
                    }),
            ])
            ->recordActions([
                $this->viewAction(),
                Action::make('edit')->label('Sửa')->iconButton()->icon('heroicon-m-pencil-square')->color('gray')
                    ->fillForm(fn (PublicProject $p) => $p->only(['code', 'name', 'developer_name', 'project_type', 'province', 'address', 'status', 'blocks', 'apartments', 'description']))
                    ->schema($this->formSchema())
                    ->action(function (PublicProject $p, array $data): void {
                        $p->update($data);
                        $this->audit('public_project.update', 'Sửa dự án: '.$p->name, PublicProject::class, $p->id);
                        Notification::make()->title('Đã lưu')->success()->send();
                    }),
                Action::make('uploadMedia')->label('Thêm media')->iconButton()->icon('heroicon-m-photo')->color('info')
                    ->schema([
                        Select::make('media_type')->label('Loại')->options(['image' => 'Ảnh', 'video' => 'Video', 'brochure' => 'Brochure', 'map' => 'Bản đồ', 'floor_plan' => 'Mặt bằng'])->default('image')->required(),
                        TextInput::make('title')->label('Tiêu đề'),
                        TextInput::make('file_url')->label('Đường dẫn tệp/URL')->required(),
                    ])
                    ->action(function (PublicProject $p, array $data): void {
                        $order = ((int) $p->media()->max('sort_order')) + 1;
                        ProjectMedia::create($data + ['public_project_id' => $p->id, 'sort_order' => $order]);
                        $this->audit('public_project.media', 'Thêm media cho '.$p->name, PublicProject::class, $p->id);
                        Notification::make()->title('Đã thêm media')->success()->send();
                    }),
                Action::make('linkTenant')->label('Liên kết công ty')->iconButton()->icon('heroicon-m-link')->color('gray')
                    ->schema([Select::make('tenant_id')->label('Công ty')->required()->searchable()->options(fn () => Tenant::pluck('name', 'id'))])
                    ->action(function (PublicProject $p, array $data): void {
                        TenantProjectLink::withoutGlobalScope('tenant')->firstOrCreate(
                            ['public_project_id' => $p->id, 'tenant_id' => $data['tenant_id']],
                            ['linked_by' => Auth::id(), 'linked_at' => now()]
                        );
                        $this->audit('public_project.link', 'Liên kết dự án '.$p->name.' → tenant #'.$data['tenant_id'], PublicProject::class, $p->id);
                        Notification::make()->title('Đã liên kết công ty')->success()->send();
                    }),
                Action::make('togglePublic')->label('Đăng/Ẩn')->iconButton()->icon('heroicon-m-globe-alt')->color('warning')
                    ->requiresConfirmation()
                    ->action(function (PublicProject $p): void {
                        $p->update(['is_public' => ! $p->is_public]);
                        $this->audit('public_project.publish', ($p->is_public ? 'Đăng' : 'Ẩn').' dự án '.$p->name, PublicProject::class, $p->id);
                        Notification::make()->title($p->is_public ? 'Đã đăng công khai' : 'Đã ẩn')->success()->send();
                    }),
            ])
            ->emptyStateHeading('Chưa có dự án')
            ->emptyStateIcon('heroicon-o-building-office-2')
            ->striped()
            ->paginated([10, 25, 50]);
    }

    public function viewAction(): Action
    {
        return Action::make('view')
            ->label('Chi tiết')->iconButton()->icon('heroicon-m-eye')->color('primary')
            ->modalHeading(fn (PublicProject $p) => $p->name)
            ->modalContent(fn (PublicProject $p) => view('filament.pages.public-project-detail', [
                'record' => $p->load(['media']),
                'links' => TenantProjectLink::withoutGlobalScope('tenant')->with('tenant')->where('public_project_id', $p->id)->get(),
                'statusMap' => self::STATUS,
            ]))
            ->modalSubmitAction(false)->modalCancelActionLabel('Đóng');
    }
}
