<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\ProvidesAiContext;
use App\Models\KnowledgeArticle;
use App\Models\KnowledgeCategory;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * WEB-UX-09-04 — Cơ sở tri thức hỗ trợ (KB). KPI strip + a Filament table over
 * knowledge_articles (search/filter/sort), a category breakdown and the X2AI
 * Support Copilot entry point (defers to the shared floating chat).
 */
class AiKnowledgeBase extends Page implements HasTable
{
    use InteractsWithTable;
    use ProvidesAiContext;

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

    protected function getViewData(): array
    {
        $helpful = (int) KnowledgeArticle::sum('helpful_count');
        $notHelpful = (int) KnowledgeArticle::sum('not_helpful_count');
        $usefulRate = ($helpful + $notHelpful) ? round($helpful / ($helpful + $notHelpful) * 100, 1) : 0;

        $this->shareAiContext([
            'title' => 'Cơ sở tri thức',
            'lines' => ['KB có '.KnowledgeArticle::where('status', 'published')->count().' bài đã xuất bản; X2AI dùng để trả lời cư dân & BQL.'],
        ]);

        return [
            'kpis' => [
                ['label' => 'Tổng bài viết', 'value' => number_format(KnowledgeArticle::count()), 'accent' => 'blue'],
                ['label' => 'Danh mục', 'value' => KnowledgeCategory::count(), 'accent' => 'teal'],
                ['label' => 'Lượt xem', 'value' => number_format((int) KnowledgeArticle::sum('views')), 'accent' => 'amber'],
                ['label' => 'Tỷ lệ hữu ích', 'value' => $usefulRate.'%', 'accent' => 'green'],
            ],
            'categories' => KnowledgeCategory::withCount('articles')->orderByDesc('articles_count')->get(),
            'topArticles' => KnowledgeArticle::where('status', 'published')->orderByDesc('views')->limit(5)->get(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(KnowledgeArticle::query()->with('category'))
            ->defaultSort('views', 'desc')
            ->columns([
                TextColumn::make('title')->label('Tiêu đề')->searchable()->wrap()
                    ->color('primary')->weight('medium'),
                TextColumn::make('category.name')->label('Danh mục')->badge()->color('gray'),
                TextColumn::make('views')->label('Lượt xem')->numeric()->sortable(),
                TextColumn::make('helpful_count')->label('Hữu ích')->sortable()
                    ->formatStateUsing(fn ($state, $record) => $record->helpful_count.' / '.($record->helpful_count + $record->not_helpful_count)),
                TextColumn::make('status')->label('Trạng thái')->badge()
                    ->formatStateUsing(fn (string $state) => self::STATUS[$state][0] ?? $state)
                    ->color(fn (string $state) => self::STATUS[$state][1] ?? 'gray'),
                TextColumn::make('published_at')->label('Xuất bản')->date('d/m/Y')->placeholder('—')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Trạng thái')
                    ->options(collect(self::STATUS)->map(fn ($v) => $v[0])->all()),
                SelectFilter::make('knowledge_category_id')->label('Danh mục')
                    ->relationship('category', 'name'),
            ])
            ->emptyStateHeading('Chưa có tài liệu')
            ->emptyStateIcon('heroicon-o-book-open')
            ->striped()
            ->paginated([10, 25, 50]);
    }
}
