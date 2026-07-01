<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\ProvidesAiContext;
use App\Filament\Concerns\WritesAudit;
use App\Models\AiPolicy;
use App\Models\AiPromptTemplate;
use App\Models\AiUsageLog;
use App\Models\KnowledgeArticle;
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

/**
 * WEB-UX-09-02 — Governance & Audit AI. KPI strip + four tabs (Nhật ký kiểm toán /
 * Chính sách AI / Nguồn dữ liệu / Prompt & phân loại). The audit tab is a real
 * Filament table over ai_usage_logs with risk-level & status badges and filters.
 */
class AiGovernance extends Page implements HasTable
{
    use InteractsWithTable;
    use ProvidesAiContext;
    use WritesAudit;

    public const POLICY_CATEGORY = [
        'data' => 'Dữ liệu', 'access' => 'Truy cập', 'risk' => 'Rủi ro', 'content' => 'Nội dung',
    ];

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|\UnitEnum|null $navigationGroup = 'X2 AI Engine';

    protected static ?string $navigationLabel = 'Governance & Audit';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Governance & Audit AI';

    protected static ?string $slug = 'ai/governance';

    protected string $view = 'filament.pages.ai-governance';

    public const RISK = [
        'low' => ['Thấp', 'gray'],
        'medium' => ['Trung bình', 'warning'],
        'high' => ['Cao', 'danger'],
    ];

    public const STATUS = [
        'success' => ['Thành công', 'success'],
        'failed' => ['Thất bại', 'danger'],
        'pending_approval' => ['Chờ duyệt', 'warning'],
        'rejected' => ['Từ chối', 'danger'],
    ];

    protected function getViewData(): array
    {
        $total = AiUsageLog::count();
        $success = AiUsageLog::where('status', 'success')->count();

        $this->shareAiContext([
            'title' => 'Kiểm soát & tuân thủ AI',
            'lines' => ['Đang theo dõi '.$total.' lượt tương tác AI và '.AiPolicy::where('status', 'active')->count().' chính sách.'],
        ]);

        return [
            'kpis' => [
                ['label' => 'Tổng lượt AI (audit)', 'value' => number_format($total), 'accent' => 'blue'],
                ['label' => 'Tỷ lệ thành công', 'value' => ($total ? round($success / $total * 100, 1) : 0).'%', 'accent' => 'green'],
                ['label' => 'Rủi ro cao chờ duyệt', 'value' => AiUsageLog::where('risk_level', 'high')->where('status', 'pending_approval')->count(), 'accent' => 'red'],
                ['label' => 'Chính sách đang áp dụng', 'value' => AiPolicy::where('status', 'active')->count(), 'accent' => 'teal'],
            ],
            'policies' => AiPolicy::orderByDesc('status')->orderBy('category')->get(),
            'prompts' => AiPromptTemplate::orderByDesc('usage_count')->get(),
            'dataSources' => AiUsageLog::selectRaw('surface, count(*) as c, max(created_at) as last_at')
                ->groupBy('surface')->orderByDesc('c')->get()
                ->map(fn ($r) => [
                    'name' => AiCenter::SURFACE_LABELS[$r->surface] ?? $r->surface,
                    'count' => $r->c,
                    'last' => \Carbon\Carbon::parse($r->last_at)->diffForHumans(),
                ]),
            'kbCount' => KnowledgeArticle::visibleTo(auth()->user())->where('status', 'published')->count(),
        ];
    }

    /** Header action: add an AI policy. */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('createPolicy')
                ->label('Thêm chính sách')->icon('heroicon-m-plus')->color('primary')
                ->modalHeading('Thêm chính sách AI')
                ->schema([
                    TextInput::make('name')->label('Tên chính sách')->required()->maxLength(160),
                    Textarea::make('description')->label('Mô tả')->rows(2)->maxLength(255),
                    Select::make('category')->label('Nhóm')->options(self::POLICY_CATEGORY)->default('data')->required(),
                    Select::make('risk_level')->label('Mức rủi ro')
                        ->options(['low' => 'Thấp', 'medium' => 'Trung bình', 'high' => 'Cao'])->default('medium')->required(),
                    Select::make('status')->label('Trạng thái')
                        ->options(['active' => 'Đang áp dụng', 'inactive' => 'Tắt'])->default('active')->required(),
                ])
                ->action(function (array $data): void {
                    $p = AiPolicy::create($data);
                    $this->audit('ai.policy.create', 'Thêm chính sách AI: '.$p->name, AiPolicy::class, $p->id);
                    Notification::make()->title('Đã thêm chính sách')->success()->send();
                }),
        ];
    }

    /** Bật/tắt một chính sách (wire:click từ tab Chính sách). */
    public function togglePolicy(int $id): void
    {
        $policy = AiPolicy::find($id);
        if (! $policy) {
            return;
        }
        $policy->update(['status' => $policy->status === 'active' ? 'inactive' : 'active']);
        $this->audit('ai.policy.toggle', ($policy->status === 'active' ? 'Bật' : 'Tắt').' chính sách: '.$policy->name, AiPolicy::class, $policy->id);
        Notification::make()->title(($policy->status === 'active' ? 'Đã bật' : 'Đã tắt').' chính sách')->success()->send();
    }

    /** Bật/tắt một prompt (wire:click từ tab Prompt & phân loại). */
    public function togglePrompt(int $id): void
    {
        $prompt = AiPromptTemplate::find($id);
        if (! $prompt) {
            return;
        }
        $prompt->update(['status' => $prompt->status === 'active' ? 'inactive' : 'active']);
        $this->audit('ai.prompt.toggle', ($prompt->status === 'active' ? 'Bật' : 'Tắt').' prompt: '.$prompt->name, AiPromptTemplate::class, $prompt->id);
        Notification::make()->title('Đã cập nhật prompt')->success()->send();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(AiUsageLog::query()->with('user'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Thời điểm')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('action')->label('Hành động')->badge()->color('gray'),
                TextColumn::make('surface')->label('Màn hình')
                    ->formatStateUsing(fn (?string $state) => AiCenter::SURFACE_LABELS[$state] ?? $state ?? '—'),
                TextColumn::make('user.name')->label('Người dùng')->placeholder('Hệ thống')->searchable(),
                TextColumn::make('model')->label('Mô hình')->badge()->color('info'),
                TextColumn::make('risk_level')->label('Rủi ro')->badge()
                    ->formatStateUsing(fn (string $state) => self::RISK[$state][0] ?? $state)
                    ->color(fn (string $state) => self::RISK[$state][1] ?? 'gray'),
                TextColumn::make('status')->label('Trạng thái')->badge()
                    ->formatStateUsing(fn (string $state) => self::STATUS[$state][0] ?? $state)
                    ->color(fn (string $state) => self::STATUS[$state][1] ?? 'gray'),
                TextColumn::make('tokens_out')->label('Tokens')->numeric()->toggleable()
                    ->formatStateUsing(fn ($state, $record) => number_format($record->tokens_in + $record->tokens_out)),
            ])
            ->filters([
                SelectFilter::make('risk_level')->label('Mức rủi ro')
                    ->options(collect(self::RISK)->map(fn ($v) => $v[0])->all()),
                SelectFilter::make('status')->label('Trạng thái')
                    ->options(collect(self::STATUS)->map(fn ($v) => $v[0])->all()),
            ])
            ->emptyStateHeading('Chưa có nhật ký AI')
            ->emptyStateIcon('heroicon-o-shield-check')
            ->striped()
            ->paginated([10, 25, 50]);
    }
}
