<?php

namespace App\Filament\Hq\Pages;

use App\Filament\Concerns\ProvidesAiContext;
use App\Filament\Concerns\WritesAudit;
use App\Models\KnowledgeArticle;
use App\Models\KnowledgeArticleShare;
use App\Models\KnowledgeCategory;
use App\Models\Project;
use App\Models\Tenant;
use App\Support\Knowledge\DocumentTextExtractor;
use App\Support\Storage\TenantStorage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * WEB-UX-09-04 — Cơ sở tri thức hỗ trợ (KB), phân quyền 3 cấp (platform/tenant/
 * project). Danh sách chỉ hiện tài liệu người dùng được XEM (scopeVisibleTo);
 * sửa/chia sẻ chỉ với tài liệu người dùng QUẢN LÝ (canManageBy). Khi lưu, trích
 * text từ body + tệp (PDF/DOCX) vào content_text để X2AI đọc.
 */
class AiKnowledgeBase extends Page implements HasTable
{
    use InteractsWithTable;
    use ProvidesAiContext;
    use WritesAudit;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static string|\UnitEnum|null $navigationGroup = 'X2 AI Engine';

    protected static ?string $navigationLabel = 'Cơ sở tri thức';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Cơ sở tri thức hỗ trợ (KB)';

    protected static ?string $slug = 'ai/knowledge';

    protected string $view = 'filament.pages.ai-knowledge-base';

    public const STATUS = [
        'published' => ['Đã xuất bản', 'success'],
        'draft' => ['Nháp', 'gray'],
        'archived' => ['Lưu trữ', 'warning'],
    ];

    public const OWNER_TONE = ['platform' => 'blue', 'tenant' => 'teal', 'project' => 'slate'];

    /** @return \Illuminate\Database\Eloquent\Builder<KnowledgeArticle> */
    private function visible()
    {
        return KnowledgeArticle::query()->visibleTo(auth()->user());
    }

    protected function getViewData(): array
    {
        $helpful = (int) (clone $this->visible())->sum('helpful_count');
        $notHelpful = (int) (clone $this->visible())->sum('not_helpful_count');
        $usefulRate = ($helpful + $notHelpful) ? round($helpful / ($helpful + $notHelpful) * 100, 1) : 0;

        // Số bài (visible) theo danh mục — không lộ tài liệu ngoài phạm vi.
        $catCounts = (clone $this->visible())->selectRaw('knowledge_category_id, count(*) as c')
            ->groupBy('knowledge_category_id')->pluck('c', 'knowledge_category_id');
        $categories = KnowledgeCategory::whereIn('id', $catCounts->keys()->filter())->get()
            ->map(fn ($c) => (object) ['name' => $c->name, 'icon' => $c->icon, 'color' => $c->color, 'articles_count' => $catCounts[$c->id] ?? 0]);

        $this->shareAiContext([
            'title' => 'Cơ sở tri thức',
            'lines' => ['Bạn có thể xem '.(clone $this->visible())->where('status', 'published')->count().' bài KB. X2AI dùng nguồn này (trong phạm vi quyền) để trả lời.'],
        ]);

        return [
            'kpis' => [
                ['label' => 'Bài xem được', 'value' => number_format((clone $this->visible())->count()), 'accent' => 'blue'],
                ['label' => 'Danh mục', 'value' => $categories->count(), 'accent' => 'teal'],
                ['label' => 'Lượt xem', 'value' => number_format((int) (clone $this->visible())->sum('views')), 'accent' => 'amber'],
                ['label' => 'Tỷ lệ hữu ích', 'value' => $usefulRate.'%', 'accent' => 'green'],
            ],
            'categories' => $categories,
            'topArticles' => (clone $this->visible())->where('status', 'published')->orderByDesc('views')->limit(5)->get(),
        ];
    }

    /** owner scope theo cấp của người tạo. */
    private function creatorOwner(): array
    {
        $u = auth()->user();
        if ($u->isPlatformAdmin()) {
            return ['owner_level' => 'platform', 'tenant_id' => null, 'project_id' => null];
        }
        if ($u->isTenantOperator()) {
            return ['owner_level' => 'tenant', 'tenant_id' => $u->tenant_id, 'project_id' => null];
        }
        $pid = $u->project_id ?? (($u->accessibleProjectIds() ?: [null])[0]);

        return ['owner_level' => 'project', 'tenant_id' => $u->tenant_id, 'project_id' => $pid];
    }

    private function extractContent(?string $body, ?array $attachments): string
    {
        return app(DocumentTextExtractor::class)->build($body, $attachments ?? []);
    }

    protected function getHeaderActions(): array
    {
        $u = auth()->user();
        $tierLabel = $u->isPlatformAdmin() ? 'Toàn hệ thống' : ($u->isTenantOperator() ? 'Công ty' : 'Dự án');

        return [
            Action::make('createArticle')
                ->label('Thêm bài viết')->icon('heroicon-m-plus')->color('primary')
                ->modalHeading('Thêm bài viết KB ('.$tierLabel.')')
                ->schema($this->articleFormSchema())
                ->action(function (array $data): void {
                    $article = KnowledgeArticle::create($data + $this->creatorOwner() + [
                        'slug' => Str::slug($data['title']),
                        'author_id' => auth()->id(),
                        'content_text' => $this->extractContent($data['body'] ?? null, $data['attachments'] ?? []),
                        'published_at' => ($data['status'] ?? 'draft') === 'published' ? now() : null,
                    ]);
                    $this->syncCategoryCount($article->knowledge_category_id);
                    $this->audit('kb.create', 'Thêm bài viết KB: '.$article->title, KnowledgeArticle::class, $article->id);
                    Notification::make()->title('Đã thêm bài viết')->success()->send();
                }),
            Action::make('createCategory')
                ->label('Thêm danh mục')->icon('heroicon-m-folder-plus')->color('gray')
                ->modalHeading('Thêm danh mục KB')
                ->schema([
                    TextInput::make('name')->label('Tên danh mục')->required()->maxLength(120),
                    TextInput::make('description')->label('Mô tả')->maxLength(255),
                    Select::make('icon')->label('Biểu tượng')->default('heroicon-o-folder')
                        ->options([
                            'heroicon-o-folder' => 'Thư mục',
                            'heroicon-o-user-group' => 'Nhóm người',
                            'heroicon-o-banknotes' => 'Tài chính',
                            'heroicon-o-wrench-screwdriver' => 'Kỹ thuật',
                            'heroicon-o-shield-check' => 'An ninh',
                            'heroicon-o-scale' => 'Pháp lý',
                        ]),
                    TextInput::make('color')->label('Màu (hex)')->default('#2563eb')->maxLength(9),
                ])
                ->action(function (array $data): void {
                    $cat = KnowledgeCategory::create($data + ['tenant_id' => auth()->user()->tenant_id, 'slug' => Str::slug($data['name']), 'articles_count' => 0]);
                    $this->audit('kb.category.create', 'Thêm danh mục KB: '.$cat->name, KnowledgeCategory::class, $cat->id);
                    Notification::make()->title('Đã thêm danh mục')->success()->send();
                }),
        ];
    }

    /** @return array<int, \Filament\Forms\Components\Component> */
    private function articleFormSchema(): array
    {
        return [
            TextInput::make('title')->label('Tiêu đề')->required()->maxLength(255)->columnSpanFull(),
            Select::make('knowledge_category_id')->label('Danh mục')
                ->options(fn () => KnowledgeCategory::orderBy('name')->pluck('name', 'id'))
                ->searchable(),
            Select::make('status')->label('Trạng thái')
                ->options(collect(self::STATUS)->map(fn ($v) => $v[0])->all())
                ->default('draft')->required(),
            TextInput::make('excerpt')->label('Tóm tắt')->maxLength(255)->columnSpanFull(),
            RichEditor::make('body')->label('Nội dung (HTML)')
                ->toolbarButtons(['bold', 'italic', 'underline', 'strike', 'h2', 'h3', 'bulletList', 'orderedList', 'link', 'blockquote', 'codeBlock', 'undo', 'redo'])
                ->columnSpanFull(),
            FileUpload::make('attachments')->label('Tệp đính kèm (để X2AI đọc)')
                ->multiple()->reorderable()->appendFiles()
                ->disk(app(TenantStorage::class)->diskName())
                ->directory(fn (): string => app(TenantStorage::class)->prefix().'/kb-attachments')->preserveFilenames()
                ->acceptedFileTypes([
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                ])
                ->maxSize(10240)
                ->helperText('Nhiều tệp .pdf / .doc / .docx (≤10MB). PDF & DOCX sẽ được X2AI đọc nội dung.')
                ->columnSpanFull(),
        ];
    }

    public function viewArticleAction(): Action
    {
        return Action::make('viewArticle')
            ->modalHeading(fn (KnowledgeArticle $record) => $record->title)
            ->modalContent(fn (KnowledgeArticle $record) => view('filament.kb.article-view', ['record' => $record]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Đóng');
    }

    public function filterByCategory(?int $id): void
    {
        $this->tableFilters['knowledge_category_id']['value'] = $id ? (string) $id : null;
        $this->resetPage();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->visible()->with(['category', 'shares']))
            ->defaultSort('views', 'desc')
            ->columns([
                TextColumn::make('title')->label('Tiêu đề')->searchable()->wrap()
                    ->color('primary')->weight('medium')
                    ->tooltip('Bấm để xem nội dung')
                    ->action($this->viewArticleAction()),
                TextColumn::make('owner_level')->label('Cấp')->badge()
                    ->formatStateUsing(fn (string $state) => KnowledgeArticle::OWNER_LEVEL[$state] ?? $state)
                    ->color(fn (string $state) => self::OWNER_TONE[$state] ?? 'gray'),
                TextColumn::make('share_mode')->label('Chia sẻ')->badge()->color('gray')
                    ->formatStateUsing(fn (string $state) => KnowledgeArticle::SHARE_MODE[$state] ?? $state),
                TextColumn::make('category.name')->label('Danh mục')->badge()->color('gray')
                    ->placeholder('—')->tooltip('Bấm để lọc theo danh mục')
                    ->action(fn (KnowledgeArticle $record) => $this->filterByCategory($record->knowledge_category_id)),
                TextColumn::make('views')->label('Lượt xem')->numeric()->sortable(),
                TextColumn::make('status')->label('Trạng thái')->badge()
                    ->formatStateUsing(fn (string $state) => self::STATUS[$state][0] ?? $state)
                    ->color(fn (string $state) => self::STATUS[$state][1] ?? 'gray'),
                TextColumn::make('published_at')->label('Xuất bản')->date('d/m/Y')->placeholder('—')->sortable(),
            ])
            ->filters([
                SelectFilter::make('owner_level')->label('Cấp sở hữu')->options(KnowledgeArticle::OWNER_LEVEL),
                SelectFilter::make('status')->label('Trạng thái')
                    ->options(collect(self::STATUS)->map(fn ($v) => $v[0])->all()),
                SelectFilter::make('knowledge_category_id')->label('Danh mục')
                    ->relationship('category', 'name'),
            ])
            ->recordActions([
                Action::make('edit')
                    ->label('Sửa')->iconButton()->icon('heroicon-m-pencil-square')->color('gray')
                    ->visible(fn (KnowledgeArticle $r) => $r->canManageBy(auth()->user()))
                    ->modalHeading('Sửa bài viết')
                    ->schema($this->articleFormSchema())
                    ->fillForm(fn (KnowledgeArticle $record) => $record->only(['title', 'knowledge_category_id', 'status', 'excerpt', 'body', 'attachments']))
                    ->action(function (KnowledgeArticle $record, array $data): void {
                        $oldCat = $record->knowledge_category_id;
                        $record->update($data + [
                            'slug' => Str::slug($data['title']),
                            'content_text' => $this->extractContent($data['body'] ?? null, $data['attachments'] ?? []),
                            'published_at' => $data['status'] === 'published' ? ($record->published_at ?? now()) : null,
                        ]);
                        $this->syncCategoryCount($oldCat);
                        $this->syncCategoryCount($record->knowledge_category_id);
                        $this->audit('kb.update', 'Sửa bài viết KB: '.$record->title, KnowledgeArticle::class, $record->id);
                        Notification::make()->title('Đã cập nhật bài viết')->success()->send();
                    }),
                $this->shareAction(),
                Action::make('publish')
                    ->label('Xuất bản')->iconButton()->icon('heroicon-m-arrow-up-circle')->color('success')
                    ->visible(fn (KnowledgeArticle $r) => $r->status !== 'published' && $r->canManageBy(auth()->user()))
                    ->requiresConfirmation()
                    ->action(fn (KnowledgeArticle $r) => $this->setStatus(collect([$r]), 'published')),
                Action::make('archive')
                    ->label('Lưu trữ')->iconButton()->icon('heroicon-m-archive-box')->color('warning')
                    ->visible(fn (KnowledgeArticle $r) => $r->status !== 'archived' && $r->canManageBy(auth()->user()))
                    ->requiresConfirmation()
                    ->action(fn (KnowledgeArticle $r) => $this->setStatus(collect([$r]), 'archived')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publish')->label('Xuất bản')->icon('heroicon-m-arrow-up-circle')->color('success')
                        ->action(fn (Collection $records) => $this->setStatus($this->manageable($records), 'published'))->deselectRecordsAfterCompletion(),
                    BulkAction::make('archive')->label('Lưu trữ')->icon('heroicon-m-archive-box')->color('warning')
                        ->action(fn (Collection $records) => $this->setStatus($this->manageable($records), 'archived'))->deselectRecordsAfterCompletion(),
                    BulkAction::make('delete')->label('Xóa')->icon('heroicon-m-trash')->color('danger')
                        ->requiresConfirmation()->modalHeading('Xóa bài viết đã chọn (chỉ tài liệu bạn quản lý)')
                        ->action(function (Collection $records): void {
                            $records = $this->manageable($records);
                            $cats = $records->pluck('knowledge_category_id')->unique();
                            $n = $records->count();
                            $records->each->delete();
                            $cats->each(fn ($c) => $this->syncCategoryCount($c));
                            $this->audit('kb.delete', 'Xóa '.$n.' bài viết KB');
                            Notification::make()->title('Đã xóa '.$n.' bài viết')->success()->send();
                        })->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->emptyStateHeading('Chưa có tài liệu trong phạm vi của bạn')
            ->emptyStateIcon('heroicon-o-book-open')
            ->striped()
            ->paginated([10, 25, 50]);
    }

    /** Chia sẻ tài liệu xuống cấp dưới (chỉ platform/tenant, và phải quản lý được). */
    private function shareAction(): Action
    {
        return Action::make('share')
            ->label('Chia sẻ')->iconButton()->icon('heroicon-m-share')->color('info')
            ->visible(fn (KnowledgeArticle $r) => $r->owner_level !== 'project' && $r->canManageBy(auth()->user()))
            ->modalHeading(fn (KnowledgeArticle $r) => 'Chia sẻ: '.$r->title)
            ->fillForm(fn (KnowledgeArticle $r) => [
                'share_mode' => $r->share_mode,
                'share_tenants' => $r->shares->where('scope_type', 'tenant')->pluck('scope_id')->all(),
                'share_projects' => $r->shares->where('scope_type', 'project')->pluck('scope_id')->all(),
            ])
            ->schema(fn (KnowledgeArticle $r) => $this->shareFormSchema($r))
            ->action(function (KnowledgeArticle $record, array $data): void {
                $record->update(['share_mode' => $data['share_mode']]);
                $record->shares()->delete();
                if ($data['share_mode'] === 'custom') {
                    foreach (($data['share_tenants'] ?? []) as $id) {
                        KnowledgeArticleShare::create(['knowledge_article_id' => $record->id, 'scope_type' => 'tenant', 'scope_id' => $id]);
                    }
                    foreach (($data['share_projects'] ?? []) as $id) {
                        KnowledgeArticleShare::create(['knowledge_article_id' => $record->id, 'scope_type' => 'project', 'scope_id' => $id]);
                    }
                }
                $this->audit('kb.share', 'Chia sẻ ('.$data['share_mode'].') bài: '.$record->title, KnowledgeArticle::class, $record->id);
                Notification::make()->title('Đã cập nhật chia sẻ')->success()->send();
            });
    }

    /** @return array<int, \Filament\Forms\Components\Component> */
    private function shareFormSchema(KnowledgeArticle $article): array
    {
        $descLabel = $article->owner_level === 'platform'
            ? 'Chia sẻ cho tất cả công ty & dự án'
            : 'Chia sẻ cho tất cả dự án trong công ty';

        $schema = [
            Select::make('share_mode')->label('Chế độ chia sẻ')->required()->live()
                ->options([
                    'private' => 'Riêng (chỉ cấp sở hữu)',
                    'descendants' => $descLabel,
                    'custom' => 'Chọn nơi chia sẻ',
                ]),
        ];

        if ($article->owner_level === 'platform') {
            $schema[] = Select::make('share_tenants')->label('Công ty được chia sẻ')
                ->multiple()->searchable()->options(fn () => Tenant::orderBy('name')->pluck('name', 'id'))
                ->visible(fn (Get $get) => $get('share_mode') === 'custom');
            $schema[] = Select::make('share_projects')->label('Dự án được chia sẻ')
                ->multiple()->searchable()->options(fn () => Project::orderBy('name')->pluck('name', 'id'))
                ->visible(fn (Get $get) => $get('share_mode') === 'custom');
        } else {
            $schema[] = Select::make('share_projects')->label('Dự án được chia sẻ')
                ->multiple()->searchable()
                ->options(fn () => Project::where('tenant_id', auth()->user()->tenant_id)->orderBy('name')->pluck('name', 'id'))
                ->visible(fn (Get $get) => $get('share_mode') === 'custom');
        }

        return $schema;
    }

    /** Chỉ giữ các bản ghi mà user quản lý được (dùng cho bulk). */
    private function manageable(Collection $records): Collection
    {
        return $records->filter(fn (KnowledgeArticle $r) => $r->canManageBy(auth()->user()))->values();
    }

    private function setStatus(Collection $records, string $status): void
    {
        if ($records->isEmpty()) {
            Notification::make()->title('Không có tài liệu bạn quản lý trong lựa chọn')->warning()->send();

            return;
        }
        $records->each(function (KnowledgeArticle $r) use ($status): void {
            $r->update([
                'status' => $status,
                'published_at' => $status === 'published' ? ($r->published_at ?? now()) : $r->published_at,
            ]);
        });
        $records->pluck('knowledge_category_id')->unique()->each(fn ($c) => $this->syncCategoryCount($c));
        $verb = self::STATUS[$status][0] ?? $status;
        $this->audit('kb.status', $verb.' '.$records->count().' bài viết KB');
        Notification::make()->title($verb.' '.$records->count().' bài viết')->success()->send();
    }

    private function syncCategoryCount(?int $categoryId): void
    {
        if ($categoryId) {
            KnowledgeCategory::whereKey($categoryId)->update([
                'articles_count' => KnowledgeArticle::where('knowledge_category_id', $categoryId)->count(),
            ]);
        }
    }
}
