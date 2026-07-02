<?php

namespace App\Filament\Sa\Pages;

use App\Filament\Concerns\PlatformScreen;
use App\Filament\Concerns\WritesAudit;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateCategory;
use App\Models\DocumentTemplateClone;
use App\Models\DocumentTemplateShare;
use App\Models\Tenant;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

/**
 * WEB-UX-22-08 — Thư viện mẫu tài liệu 3 cấp (platform/company/project).
 *
 * Mẫu có version + owner_scope + status + template_type (AC-16).
 * Chia sẻ KHÔNG đổi owner (AC-17); Clone tạo mẫu MỚI với owner mới (AC-18).
 */
class DocumentTemplateLibrary extends Page implements HasTable
{
    use InteractsWithTable;
    use PlatformScreen;
    use WritesAudit;

    protected static function platformFeature(): ?string
    {
        return 'document_template';
    }

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-duplicate';

    protected static string|\UnitEnum|null $navigationGroup = 'Nền tảng (SuperAdmin)';

    protected static ?string $navigationLabel = 'Mẫu tài liệu';

    protected static ?int $navigationSort = 50;

    protected static ?string $title = 'Thư viện mẫu tài liệu';

    protected static ?string $slug = 'platform/document-templates';

    protected string $view = 'filament.pages.document-template-library';

    public const TYPE = [
        'notice' => 'Thông báo', 'sop' => 'SOP', 'policy' => 'Chính sách', 'contract' => 'Hợp đồng',
        'checklist' => 'Checklist', 'form' => 'Biểu mẫu', 'fee_template' => 'Mẫu phí', 'pccc' => 'PCCC', 'maintenance' => 'Bảo trì',
    ];

    public const SCOPE = ['platform' => 'Nền tảng', 'tenant' => 'Công ty', 'company' => 'Tập đoàn', 'project' => 'Dự án', 'building' => 'Tòa'];

    public const STATUS = [
        'draft' => ['Nháp', 'gray'], 'active' => ['Đang dùng', 'success'],
        'deprecated' => ['Ngừng dùng', 'warning'], 'archived' => ['Lưu trữ', 'gray'],
    ];

    public const SHARE_MODE = ['view_only' => 'Chỉ xem', 'use_as_template' => 'Dùng làm mẫu', 'clone_allowed' => 'Cho phép clone', 'force_apply' => 'Áp dụng bắt buộc'];

    protected function getViewData(): array
    {
        $c = fn (string $s) => DocumentTemplate::where('status', $s)->count();

        return [
            'kpis' => [
                ['label' => 'Tổng mẫu', 'value' => DocumentTemplate::count(), 'accent' => 'blue'],
                ['label' => 'Đang dùng', 'value' => $c('active'), 'accent' => 'green'],
                ['label' => 'Nháp', 'value' => $c('draft'), 'accent' => 'amber'],
                ['label' => 'AI đọc được', 'value' => DocumentTemplate::where('ai_readable', true)->count(), 'accent' => 'blue'],
            ],
        ];
    }

    /** @return array<\Filament\Forms\Components\Component> */
    private function formSchema(): array
    {
        return [
            TextInput::make('code')->label('Mã')->required()->maxLength(50),
            TextInput::make('title')->label('Tiêu đề')->required()->maxLength(255),
            Select::make('template_type')->label('Loại')->options(self::TYPE)->required()->default('notice'),
            Select::make('category_id')->label('Danh mục')->options(fn () => DocumentTemplateCategory::pluck('name', 'id')),
            Select::make('owner_scope')->label('Cấp sở hữu')->options(self::SCOPE)->required()->default('platform'),
            Toggle::make('ai_readable')->label('Cho AI đọc')->default(true),
            Textarea::make('description')->label('Mô tả')->rows(2)->columnSpanFull(),
            Textarea::make('body_markdown')->label('Nội dung (markdown)')->rows(6)->columnSpanFull(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(DocumentTemplate::query()->with('category')->withCount(['shares', 'clones']))
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('code')->label('Mã')->searchable()->color('primary')->weight('medium'),
                TextColumn::make('title')->label('Tiêu đề')->searchable()->wrap(),
                TextColumn::make('template_type')->label('Loại')->badge()->color('gray')
                    ->formatStateUsing(fn (string $state) => self::TYPE[$state] ?? $state),
                TextColumn::make('owner_scope')->label('Cấp')->badge()->color('info')
                    ->formatStateUsing(fn (string $state) => self::SCOPE[$state] ?? $state),
                TextColumn::make('version')->label('Ver')->formatStateUsing(fn ($state) => 'v'.$state)->alignCenter(),
                IconColumn::make('ai_readable')->label('AI')->boolean()->alignCenter(),
                TextColumn::make('shares_count')->label('Chia sẻ')->badge()->color('gray')->alignCenter()->toggleable(),
                TextColumn::make('status')->label('Trạng thái')->badge()
                    ->formatStateUsing(fn (string $state) => self::STATUS[$state][0] ?? $state)
                    ->color(fn (string $state) => self::STATUS[$state][1] ?? 'gray'),
            ])
            ->filters([
                SelectFilter::make('status')->label('Trạng thái')->options(collect(self::STATUS)->map(fn ($v) => $v[0])->all()),
                SelectFilter::make('template_type')->label('Loại')->options(self::TYPE),
                SelectFilter::make('owner_scope')->label('Cấp sở hữu')->options(self::SCOPE),
            ])
            ->headerActions([
                Action::make('create')->label('Tạo mẫu')->icon('heroicon-m-plus')->color('primary')
                    ->schema($this->formSchema())
                    ->action(function (array $data): void {
                        $data['status'] = 'draft';
                        $data['version'] = 1;
                        $data['created_by'] = Auth::id();
                        $t = DocumentTemplate::create($data);
                        $this->audit('template.create', 'Tạo mẫu: '.$t->title, DocumentTemplate::class, $t->id);
                        Notification::make()->title('Đã tạo mẫu (nháp)')->success()->send();
                    }),
            ])
            ->recordActions([
                $this->viewAction(),
                Action::make('edit')->label('Sửa')->iconButton()->icon('heroicon-m-pencil-square')->color('gray')
                    ->visible(fn (DocumentTemplate $t) => $t->status !== 'archived')
                    ->fillForm(fn (DocumentTemplate $t) => $t->only(['code', 'title', 'template_type', 'category_id', 'owner_scope', 'ai_readable', 'description', 'body_markdown']))
                    ->schema($this->formSchema())
                    ->action(function (DocumentTemplate $t, array $data): void {
                        $t->update($data);
                        $this->audit('template.update', 'Sửa mẫu: '.$t->title, DocumentTemplate::class, $t->id);
                        Notification::make()->title('Đã lưu')->success()->send();
                    }),
                Action::make('activate')->label('Duyệt & kích hoạt')->iconButton()->icon('heroicon-m-check-circle')->color('success')
                    ->visible(fn (DocumentTemplate $t) => $t->status === 'draft')
                    ->requiresConfirmation()
                    ->action(function (DocumentTemplate $t): void {
                        $t->update(['status' => 'active', 'approved_by' => Auth::id(), 'effective_from' => now()]);
                        $this->audit('template.activate', 'Kích hoạt mẫu: '.$t->title, DocumentTemplate::class, $t->id);
                        Notification::make()->title('Đã kích hoạt')->success()->send();
                    }),
                Action::make('deprecate')->label('Ngừng dùng')->iconButton()->icon('heroicon-m-archive-box-x-mark')->color('warning')
                    ->visible(fn (DocumentTemplate $t) => $t->status === 'active')
                    ->requiresConfirmation()
                    ->action(function (DocumentTemplate $t): void {
                        $t->update(['status' => 'deprecated', 'effective_to' => now()]);
                        $this->audit('template.deprecate', 'Ngừng mẫu: '.$t->title, DocumentTemplate::class, $t->id);
                        Notification::make()->title('Đã ngừng dùng')->success()->send();
                    }),
                Action::make('share')->label('Chia sẻ')->iconButton()->icon('heroicon-m-share')->color('info')
                    ->schema([
                        Select::make('to_scope')->label('Chia sẻ tới cấp')->options(self::SCOPE)->required()->default('tenant'),
                        Select::make('to_owner_id')->label('Công ty (nếu chọn cấp công ty)')->options(fn () => Tenant::pluck('name', 'id')),
                        Select::make('share_mode')->label('Chế độ')->options(self::SHARE_MODE)->required()->default('use_as_template'),
                        Toggle::make('can_ai_read')->label('Cho AI đọc')->default(true),
                    ])
                    ->action(fn (DocumentTemplate $t, array $data) => $this->share($t, $data)),
                Action::make('clone')->label('Clone')->iconButton()->icon('heroicon-m-document-duplicate')->color('gray')
                    ->schema([
                        Select::make('owner_scope')->label('Cấp sở hữu mới')->options(self::SCOPE)->required()->default('tenant'),
                        TextInput::make('clone_reason')->label('Lý do clone'),
                    ])
                    ->action(fn (DocumentTemplate $t, array $data) => $this->clone($t, $data)),
            ])
            ->emptyStateHeading('Chưa có mẫu tài liệu')
            ->emptyStateIcon('heroicon-o-document-duplicate')
            ->striped()
            ->paginated([10, 25, 50]);
    }

    public function viewAction(): Action
    {
        return Action::make('view')
            ->label('Chi tiết')->iconButton()->icon('heroicon-m-eye')->color('primary')
            ->modalHeading(fn (DocumentTemplate $t) => $t->code.' — '.$t->title)
            ->modalContent(fn (DocumentTemplate $t) => view('filament.pages.template-detail', [
                'record' => $t->load(['category', 'shares', 'clones']),
                'typeMap' => self::TYPE, 'scopeMap' => self::SCOPE, 'statusMap' => self::STATUS, 'shareModeMap' => self::SHARE_MODE,
            ]))
            ->modalSubmitAction(false)->modalCancelActionLabel('Đóng');
    }

    private function share(DocumentTemplate $t, array $data): void
    {
        // AC-17: chia sẻ KHÔNG đổi owner — chỉ tạo bản ghi share.
        DocumentTemplateShare::create([
            'template_id' => $t->id,
            'from_scope' => $t->owner_scope, 'from_owner_id' => $t->owner_id,
            'to_scope' => $data['to_scope'], 'to_owner_id' => $data['to_owner_id'] ?? null,
            'share_mode' => $data['share_mode'], 'can_ai_read' => $data['can_ai_read'] ?? true,
            'status' => 'active', 'effective_from' => now(),
        ]);
        $this->audit('template.share', 'Chia sẻ mẫu '.$t->title.' → '.self::SCOPE[$data['to_scope']], DocumentTemplate::class, $t->id);
        Notification::make()->title('Đã chia sẻ (owner không đổi)')->success()->send();
    }

    private function clone(DocumentTemplate $t, array $data): void
    {
        // AC-18: clone tạo mẫu MỚI với owner mới.
        $copy = $t->replicate(['approved_by', 'effective_from', 'effective_to']);
        $copy->code = $t->code.'-C'.(DocumentTemplate::where('code', 'like', $t->code.'-C%')->count() + 1);
        $copy->title = $t->title.' (clone)';
        $copy->owner_scope = $data['owner_scope'];
        $copy->owner_id = Auth::user()->tenant_id;
        $copy->status = 'draft';
        $copy->version = 1;
        $copy->created_by = Auth::id();
        $copy->save();

        DocumentTemplateClone::create([
            'source_template_id' => $t->id, 'cloned_template_id' => $copy->id,
            'cloned_by' => Auth::id(), 'cloned_at' => now(), 'clone_reason' => $data['clone_reason'] ?? null,
        ]);
        $this->audit('template.clone', 'Clone mẫu '.$t->title.' → '.$copy->code, DocumentTemplate::class, $copy->id);
        Notification::make()->title('Đã clone thành mẫu mới '.$copy->code)->success()->send();
    }
}
