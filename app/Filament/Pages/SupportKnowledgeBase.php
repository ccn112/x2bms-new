<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\PlatformScreen;
use App\Filament\Concerns\WritesSupportAudit;
use App\Models\SupportKbArticle;
use App\Models\SupportKbArticleVersion;
use App\Models\SupportKbCategory;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/**
 * WEB-UX-30-08 — Support Knowledge Base.
 * SOP/runbook/FAQ. Title click → chi tiết; tạo mới dùng RichEditor cho nội dung;
 * versioning + publish/archive. Audit đầy đủ.
 */
class SupportKnowledgeBase extends Page implements HasTable
{
    use InteractsWithTable;
    use PlatformScreen;
    use WritesSupportAudit;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static string|\UnitEnum|null $navigationGroup = 'Support Center';

    protected static ?string $navigationLabel = 'Support KB';

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'Kho tri thức hỗ trợ';

    protected static ?string $slug = 'support/knowledge-base';

    protected string $view = 'filament.pages.support-knowledge-base';

    public const STATUS = ['draft' => ['Nháp', 'gray'], 'in_review' => ['Chờ duyệt', 'warning'], 'published' => ['Đã đăng', 'success'], 'archived' => ['Lưu trữ', 'gray']];

    protected function getViewData(): array
    {
        return [
            'kpis' => [
                ['label' => 'Tổng bài', 'value' => SupportKbArticle::count(), 'accent' => 'blue'],
                ['label' => 'Đã đăng', 'value' => SupportKbArticle::where('status', 'published')->count(), 'accent' => 'green'],
                ['label' => 'Nháp', 'value' => SupportKbArticle::whereIn('status', ['draft', 'in_review'])->count(), 'accent' => 'amber'],
                ['label' => 'Lượt xem', 'value' => number_format((int) SupportKbArticle::sum('views')), 'accent' => 'blue'],
                ['label' => 'Rating TB', 'value' => number_format((float) SupportKbArticle::whereNotNull('rating')->avg('rating'), 1), 'accent' => 'green'],
            ],
            'popular' => SupportKbArticle::orderByDesc('views')->limit(5)->get(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')->label('Tạo bài viết')->icon('heroicon-m-plus')->color('primary')->modalWidth('3xl')
                ->schema([
                    TextInput::make('title')->label('Tiêu đề')->required()->maxLength(255)->columnSpanFull(),
                    Select::make('category_id')->label('Danh mục')->options(SupportKbCategory::pluck('name', 'id')),
                    RichEditor::make('body')->label('Nội dung')->columnSpanFull(),
                ])
                ->action(function (array $data): void {
                    $art = SupportKbArticle::create($data + ['code' => 'KB-SUP-'.strtoupper(Str::random(4)), 'status' => 'draft', 'author_id' => auth()->id()]);
                    SupportKbArticleVersion::create(['support_kb_article_id' => $art->id, 'version' => 1, 'body' => $art->body, 'editor_id' => auth()->id(), 'created_at' => now()]);
                    $this->supportAudit('kb.created', $art, after: ['code' => $art->code]);
                    Notification::make()->title('Đã tạo bài viết (nháp)')->success()->send();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(SupportKbArticle::query()->with('category'))
            ->defaultSort('views', 'desc')
            ->columns([
                TextColumn::make('code')->label('Mã')->fontFamily('mono')->size('xs')->searchable(),
                TextColumn::make('title')->label('Tiêu đề')->wrap()->weight('medium')->color('primary')->searchable()->action($this->detailAction()),
                TextColumn::make('category.name')->label('Danh mục')->badge()->color('gray'),
                TextColumn::make('status')->label('Trạng thái')->badge()
                    ->formatStateUsing(fn (string $state) => self::STATUS[$state][0] ?? $state)->color(fn (string $state) => self::STATUS[$state][1] ?? 'gray'),
                TextColumn::make('rating')->label('Rating')->formatStateUsing(fn ($state) => $state !== null ? number_format((float) $state, 1) : '—'),
                TextColumn::make('views')->label('Lượt xem')->numeric()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Trạng thái')->options(collect(self::STATUS)->map(fn ($v) => $v[0])->all()),
                SelectFilter::make('category_id')->label('Danh mục')->options(SupportKbCategory::pluck('name', 'id')),
            ])
            ->recordActions([
                $this->detailAction(),
                Action::make('publish')->label('Đăng')->iconButton()->icon('heroicon-m-paper-airplane')->color('success')
                    ->visible(fn (SupportKbArticle $r) => $r->status !== 'published')->requiresConfirmation()
                    ->action(function (SupportKbArticle $r): void {
                        $r->update(['status' => 'published', 'published_at' => now()]);
                        $this->supportAudit('kb.published', $r);
                        Notification::make()->title('Đã đăng bài viết')->success()->send();
                    }),
                Action::make('archive')->label('Lưu trữ')->iconButton()->icon('heroicon-m-archive-box')->color('gray')
                    ->visible(fn (SupportKbArticle $r) => $r->status !== 'archived')->requiresConfirmation()
                    ->action(function (SupportKbArticle $r): void {
                        $r->update(['status' => 'archived']);
                        $this->supportAudit('kb.archived', $r);
                        Notification::make()->title('Đã lưu trữ')->success()->send();
                    }),
            ])
            ->emptyStateHeading('Chưa có bài viết')
            ->striped();
    }

    public function detailAction(): Action
    {
        return Action::make('detail')->label('Chi tiết')->iconButton()->icon('heroicon-m-eye')->color('primary')
            ->modalHeading(fn (SupportKbArticle $r) => $r->title)
            ->modalContent(fn (SupportKbArticle $r) => view('filament.pages.support-kb-detail', ['record' => $r->load('category', 'author')]))
            ->modalWidth('3xl')->modalSubmitAction(false)->modalCancelActionLabel('Đóng');
    }
}
