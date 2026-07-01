<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\PlatformScreen;
use App\Filament\Concerns\WritesAudit;
use App\Models\KnowledgeDocument;
use App\Models\Tenant;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

/**
 * WEB-UX-22-10 — Thư viện KB nền tảng cho X2AI + vận hành.
 *
 * KB có ai_index_status + sensitivity (AC-20). AI không lấy tài liệu archived/hết hạn (AC-21).
 * Tài liệu restricted cần ai_read scope rõ ràng (AC-22). Index lỗi có nút thử lại.
 */
class PlatformKnowledgeBase extends Page implements HasTable
{
    use InteractsWithTable;
    use PlatformScreen;
    use WritesAudit;

    protected static function platformFeature(): ?string
    {
        return 'rag';
    }

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static string|\UnitEnum|null $navigationGroup = 'Nền tảng (SuperAdmin)';

    protected static ?string $navigationLabel = 'KB nền tảng';

    protected static ?int $navigationSort = 60;

    protected static ?string $title = 'Thư viện tri thức nền tảng (AI)';

    protected static ?string $slug = 'platform/knowledge-base';

    protected string $view = 'filament.pages.platform-knowledge-base';

    public const TYPE = [
        'policy' => 'Chính sách', 'sop' => 'SOP', 'faq' => 'FAQ', 'guide' => 'Hướng dẫn',
        'manual' => 'Cẩm nang', 'legal' => 'Pháp lý', 'maintenance' => 'Bảo trì', 'resident_rule' => 'Nội quy',
    ];

    public const SCOPE = ['platform' => 'Nền tảng', 'tenant' => 'Công ty', 'company' => 'Tập đoàn', 'project' => 'Dự án', 'building' => 'Tòa'];

    public const SENSITIVITY = [
        'public' => ['Công khai', 'success'], 'internal' => ['Nội bộ', 'info'],
        'confidential' => ['Mật', 'warning'], 'restricted' => ['Hạn chế', 'danger'],
    ];

    public const INDEX = [
        'not_indexed' => ['Chưa index', 'gray'], 'queued' => ['Đang chờ', 'info'],
        'indexed' => ['Đã index', 'success'], 'failed' => ['Lỗi', 'danger'],
    ];

    protected function getViewData(): array
    {
        $soon = KnowledgeDocument::whereNotNull('effective_to')
            ->whereBetween('effective_to', [now(), now()->addDays(30)])->count();

        return [
            'kpis' => [
                ['label' => 'Tổng KB', 'value' => KnowledgeDocument::count(), 'accent' => 'blue'],
                ['label' => 'Đã index AI', 'value' => KnowledgeDocument::where('ai_index_status', 'indexed')->count(), 'accent' => 'green'],
                ['label' => 'Index lỗi', 'value' => KnowledgeDocument::where('ai_index_status', 'failed')->count(), 'accent' => 'red'],
                ['label' => 'Hạn chế', 'value' => KnowledgeDocument::where('sensitivity', 'restricted')->count(), 'accent' => 'amber'],
                ['label' => 'Sắp hết hạn', 'value' => $soon, 'accent' => 'amber'],
            ],
        ];
    }

    /** @return array<\Filament\Forms\Components\Component> */
    private function formSchema(): array
    {
        return [
            TextInput::make('code')->label('Mã')->required()->maxLength(50),
            TextInput::make('title')->label('Tiêu đề')->required()->maxLength(255),
            Select::make('document_type')->label('Loại')->options(self::TYPE)->required()->default('policy'),
            Select::make('owner_scope')->label('Cấp sở hữu')->options(self::SCOPE)->required()->default('platform'),
            Select::make('sensitivity')->label('Độ nhạy cảm')->options(collect(self::SENSITIVITY)->map(fn ($v) => $v[0])->all())->required()->default('internal'),
            Select::make('language')->label('Ngôn ngữ')->options(['vi' => 'Tiếng Việt', 'en' => 'English'])->default('vi'),
            Textarea::make('description')->label('Mô tả')->rows(2)->columnSpanFull(),
            Textarea::make('content_markdown')->label('Nội dung')->rows(6)->columnSpanFull(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(KnowledgeDocument::query())
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('code')->label('Mã')->searchable()->color('primary')->weight('medium'),
                TextColumn::make('title')->label('Tiêu đề')->searchable()->wrap(),
                TextColumn::make('document_type')->label('Loại')->badge()->color('gray')
                    ->formatStateUsing(fn (string $state) => self::TYPE[$state] ?? $state),
                TextColumn::make('owner_scope')->label('Cấp')->badge()->color('info')
                    ->formatStateUsing(fn (string $state) => self::SCOPE[$state] ?? $state),
                TextColumn::make('sensitivity')->label('Độ nhạy')->badge()
                    ->formatStateUsing(fn (string $state) => self::SENSITIVITY[$state][0] ?? $state)
                    ->color(fn (string $state) => self::SENSITIVITY[$state][1] ?? 'gray'),
                TextColumn::make('ai_index_status')->label('AI Index')->badge()
                    ->formatStateUsing(fn (string $state) => self::INDEX[$state][0] ?? $state)
                    ->color(fn (string $state) => self::INDEX[$state][1] ?? 'gray'),
                TextColumn::make('version')->label('Ver')->formatStateUsing(fn ($state) => 'v'.$state)->alignCenter()->toggleable(),
                TextColumn::make('effective_to')->label('Hết hạn')->date('d/m/Y')->placeholder('—')->toggleable(),
                TextColumn::make('status')->label('TT')->badge()
                    ->color(fn (string $state) => $state === 'active' ? 'success' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('document_type')->label('Loại')->options(self::TYPE),
                SelectFilter::make('sensitivity')->label('Độ nhạy')->options(collect(self::SENSITIVITY)->map(fn ($v) => $v[0])->all()),
                SelectFilter::make('ai_index_status')->label('AI Index')->options(collect(self::INDEX)->map(fn ($v) => $v[0])->all()),
                SelectFilter::make('owner_scope')->label('Cấp')->options(self::SCOPE),
            ])
            ->headerActions([
                Action::make('create')->label('Thêm tài liệu KB')->icon('heroicon-m-plus')->color('primary')
                    ->schema($this->formSchema())
                    ->action(function (array $data): void {
                        $data['status'] = 'active';
                        $data['version'] = 1;
                        $data['ai_index_status'] = 'not_indexed';
                        $d = KnowledgeDocument::create($data);
                        $this->audit('kb.create', 'Tạo KB: '.$d->title, KnowledgeDocument::class, $d->id);
                        Notification::make()->title('Đã thêm tài liệu KB')->success()->send();
                    }),
            ])
            ->recordActions([
                $this->viewAction(),
                Action::make('edit')->label('Sửa')->iconButton()->icon('heroicon-m-pencil-square')->color('gray')
                    ->visible(fn (KnowledgeDocument $d) => $d->status !== 'archived')
                    ->fillForm(fn (KnowledgeDocument $d) => $d->only(['code', 'title', 'document_type', 'owner_scope', 'sensitivity', 'language', 'description', 'content_markdown']))
                    ->schema($this->formSchema())
                    ->action(function (KnowledgeDocument $d, array $data): void {
                        $d->update($data);
                        $this->audit('kb.update', 'Sửa KB: '.$d->title, KnowledgeDocument::class, $d->id);
                        Notification::make()->title('Đã lưu')->success()->send();
                    }),
                Action::make('index')->label('Index AI')->iconButton()->icon('heroicon-m-cpu-chip')->color('info')
                    ->visible(fn (KnowledgeDocument $d) => in_array($d->ai_index_status, ['not_indexed', 'failed'], true) && $d->status === 'active')
                    ->requiresConfirmation()->modalDescription('Đưa tài liệu vào chỉ mục AI để X2AI có thể truy xuất.')
                    ->action(fn (KnowledgeDocument $d) => $this->indexDoc($d)),
                Action::make('reindex')->label('Index lại')->iconButton()->icon('heroicon-m-arrow-path')->color('gray')
                    ->visible(fn (KnowledgeDocument $d) => $d->ai_index_status === 'indexed')
                    ->action(fn (KnowledgeDocument $d) => $this->indexDoc($d, true)),
                Action::make('archive')->label('Lưu trữ')->iconButton()->icon('heroicon-m-archive-box')->color('warning')
                    ->visible(fn (KnowledgeDocument $d) => $d->status === 'active')
                    ->requiresConfirmation()->modalDescription('Tài liệu lưu trữ sẽ KHÔNG được AI truy xuất nữa.')
                    ->action(function (KnowledgeDocument $d): void {
                        $d->update(['status' => 'archived', 'ai_index_status' => 'not_indexed']);
                        $this->audit('kb.archive', 'Lưu trữ KB: '.$d->title, KnowledgeDocument::class, $d->id);
                        Notification::make()->title('Đã lưu trữ')->success()->send();
                    }),
                Action::make('share')->label('Chia sẻ')->iconButton()->icon('heroicon-m-share')->color('gray')
                    ->schema([
                        Select::make('scope_type')->label('Tới cấp')->options(self::SCOPE + ['role' => 'Vai trò', 'user' => 'Người dùng'])->required()->default('tenant'),
                        Select::make('scope_id')->label('Công ty (nếu chọn cấp công ty)')->options(fn () => Tenant::pluck('name', 'id')),
                        Toggle::make('ai_read')->label('Cho AI đọc (ai_read)')->default(false),
                    ])
                    ->action(function (KnowledgeDocument $d, array $data): void {
                        $d->scopes()->create([
                            'scope_type' => $data['scope_type'], 'scope_id' => $data['scope_id'] ?? null,
                            'permission' => ($data['ai_read'] ?? false) ? 'ai_read' : 'read', 'status' => 'active',
                        ]);
                        $this->audit('kb.share', 'Chia sẻ KB '.$d->title.' → '.$data['scope_type'], KnowledgeDocument::class, $d->id);
                        Notification::make()->title('Đã chia sẻ KB')->success()->send();
                    }),
            ])
            ->emptyStateHeading('Chưa có tài liệu KB')
            ->emptyStateIcon('heroicon-o-book-open')
            ->striped()
            ->paginated([10, 25, 50]);
    }

    public function viewAction(): Action
    {
        return Action::make('view')
            ->label('Chi tiết')->iconButton()->icon('heroicon-m-eye')->color('primary')
            ->modalHeading(fn (KnowledgeDocument $d) => $d->code.' — '.$d->title)
            ->modalContent(fn (KnowledgeDocument $d) => view('filament.pages.kb-document-detail', [
                'record' => $d->load('scopes'),
                'typeMap' => self::TYPE, 'scopeMap' => self::SCOPE,
                'sensitivityMap' => self::SENSITIVITY, 'indexMap' => self::INDEX,
            ]))
            ->modalSubmitAction(false)->modalCancelActionLabel('Đóng');
    }

    private function indexDoc(KnowledgeDocument $d, bool $re = false): void
    {
        // Mô phỏng đưa vào chỉ mục AI (thực tế nối hàng đợi embed sau).
        $d->update(['ai_index_status' => 'indexed', 'ai_indexed_at' => now()]);
        $this->audit('kb.index_ai', ($re ? 'Index lại' : 'Index').' KB: '.$d->title, KnowledgeDocument::class, $d->id);
        Notification::make()->title($re ? 'Đã index lại' : 'Đã đưa vào chỉ mục AI')->success()->send();
    }
}
