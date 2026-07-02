<?php

namespace App\Filament\Sa\Pages;

use App\Filament\Concerns\PlatformScreen;
use App\Filament\Concerns\WritesAudit;
use App\Models\AiGuardrailPolicy;
use App\Models\AiPromptTemplate;
use App\Models\AiRetrievalLog;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

/**
 * WEB-UX-22-11 — Cấu hình AI đọc KB: prompt template, guardrail, phạm vi truy xuất.
 *
 * Prompt hỗ trợ biến (AC-23). Guardrail action warn|block|require_human_approval|log_only (AC-24).
 * Phạm vi truy xuất phải tôn trọng tenant/project/building/role/user (thể hiện qua KB scopes).
 */
class AiKnowledgeConfig extends Page implements HasTable
{
    use InteractsWithTable;
    use PlatformScreen;
    use WritesAudit;

    protected static function platformFeature(): ?string
    {
        return 'prompt_guardrail';
    }

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static string|\UnitEnum|null $navigationGroup = 'Nền tảng (SuperAdmin)';

    protected static ?string $navigationLabel = 'Cấu hình AI tri thức';

    protected static ?int $navigationSort = 61;

    protected static ?string $title = 'Cấu hình AI đọc tri thức';

    protected static ?string $slug = 'platform/ai-knowledge-config';

    protected string $view = 'filament.pages.ai-knowledge-config';

    public const USE_CASE = [
        'resident_qa' => 'Hỏi đáp cư dân', 'bql_copilot' => 'Trợ lý BQL', 'support_agent' => 'CSKH',
        'finance_explain' => 'Giải thích tài chính', 'work_order_triage' => 'Phân loại công việc',
    ];

    public const GUARD_ACTION = [
        'warn' => ['Cảnh báo', 'warning'], 'block' => ['Chặn', 'danger'],
        'require_human_approval' => ['Cần người duyệt', 'info'], 'log_only' => ['Chỉ ghi log', 'gray'],
    ];

    private function prompts()
    {
        return AiPromptTemplate::withoutGlobalScope('tenant');
    }

    protected function getViewData(): array
    {
        $tokenMonth = (int) AiRetrievalLog::whereBetween('created_at', [now()->startOfMonth(), now()])
            ->sum(\DB::raw('token_input + token_output'));
        $blocked = AiRetrievalLog::whereNotNull('blocked_document_ids_json')
            ->where('blocked_document_ids_json', '!=', '[]')->count();

        return [
            'kpis' => [
                ['label' => 'Prompt đang bật', 'value' => $this->prompts()->where('status', 'active')->count(), 'accent' => 'green'],
                ['label' => 'Guardrail đang bật', 'value' => AiGuardrailPolicy::where('is_active', true)->count(), 'accent' => 'blue'],
                ['label' => 'Token tháng này', 'value' => number_format($tokenMonth), 'accent' => 'blue'],
                ['label' => 'Lượt bị chặn', 'value' => $blocked, 'accent' => $blocked > 0 ? 'red' : 'green'],
            ],
            'guardrails' => AiGuardrailPolicy::orderByDesc('is_active')->orderBy('severity')->get(),
            'guardActionMap' => self::GUARD_ACTION,
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->prompts())
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('code')->label('Mã')->searchable()->color('primary')->placeholder('—'),
                TextColumn::make('name')->label('Tên prompt')->searchable()->weight('medium')->wrap(),
                TextColumn::make('use_case')->label('Use case')->badge()->color('info')->placeholder('—')
                    ->formatStateUsing(fn (?string $state) => self::USE_CASE[$state] ?? $state),
                TextColumn::make('owner_scope')->label('Cấp')->badge()->color('gray')->placeholder('—'),
                TextColumn::make('usage_count')->label('Lượt dùng')->numeric()->alignCenter()->toggleable(),
                TextColumn::make('status')->label('Trạng thái')->badge()
                    ->color(fn (?string $state) => $state === 'active' ? 'success' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('use_case')->label('Use case')->options(self::USE_CASE),
                SelectFilter::make('status')->label('Trạng thái')->options(['active' => 'Bật', 'inactive' => 'Tắt', 'draft' => 'Nháp']),
            ])
            ->headerActions([
                Action::make('create')->label('Tạo prompt')->icon('heroicon-m-plus')->color('primary')
                    ->schema($this->promptForm())
                    ->action(function (array $data): void {
                        $data['status'] = 'active';
                        $data['owner_scope'] = 'platform';
                        $p = AiPromptTemplate::withoutGlobalScope('tenant')->create($data);
                        $this->audit('ai.prompt_create', 'Tạo prompt: '.$p->name, AiPromptTemplate::class, $p->id);
                        Notification::make()->title('Đã tạo prompt')->success()->send();
                    }),
            ])
            ->recordActions([
                Action::make('test')->label('Thử prompt')->iconButton()->icon('heroicon-m-beaker')->color('info')
                    ->schema([TextInput::make('vars')->label('Giá trị biến (mô phỏng)')->placeholder('vd: căn A-1203')])
                    ->modalHeading(fn (AiPromptTemplate $p) => 'Thử: '.$p->name)
                    ->modalContent(fn (AiPromptTemplate $p) => view('filament.pages.prompt-test', [
                        'record' => $p, 'useCaseMap' => self::USE_CASE,
                    ]))
                    ->action(fn () => Notification::make()->title('Đã dựng prompt thử (mô phỏng)')->success()->send()),
                Action::make('edit')->label('Sửa')->iconButton()->icon('heroicon-m-pencil-square')->color('gray')
                    ->fillForm(fn (AiPromptTemplate $p) => $p->only(['code', 'name', 'use_case', 'category', 'system_prompt', 'user_prompt_template']))
                    ->schema($this->promptForm())
                    ->action(function (AiPromptTemplate $p, array $data): void {
                        $p->update($data);
                        $this->audit('ai.prompt_update', 'Sửa prompt: '.$p->name, AiPromptTemplate::class, $p->id);
                        Notification::make()->title('Đã lưu')->success()->send();
                    }),
                Action::make('toggle')->label('Bật/Tắt')->iconButton()
                    ->icon(fn (AiPromptTemplate $p) => $p->status === 'active' ? 'heroicon-m-pause-circle' : 'heroicon-m-play-circle')
                    ->color(fn (AiPromptTemplate $p) => $p->status === 'active' ? 'warning' : 'success')
                    ->action(function (AiPromptTemplate $p): void {
                        $p->update(['status' => $p->status === 'active' ? 'inactive' : 'active']);
                        $this->audit('ai.prompt_toggle', ($p->status === 'active' ? 'Bật' : 'Tắt').' prompt: '.$p->name, AiPromptTemplate::class, $p->id);
                        Notification::make()->title($p->status === 'active' ? 'Đã bật' : 'Đã tắt')->success()->send();
                    }),
            ])
            ->emptyStateHeading('Chưa có prompt')
            ->emptyStateIcon('heroicon-o-command-line')
            ->striped()
            ->paginated([10, 25]);
    }

    /** @return array<\Filament\Forms\Components\Component> */
    private function promptForm(): array
    {
        return [
            TextInput::make('code')->label('Mã')->maxLength(50),
            TextInput::make('name')->label('Tên prompt')->required()->maxLength(255),
            Select::make('use_case')->label('Use case')->options(self::USE_CASE),
            TextInput::make('category')->label('Nhóm')->maxLength(100),
            Textarea::make('system_prompt')->label('System prompt')->rows(3)->columnSpanFull(),
            Textarea::make('user_prompt_template')->label('Mẫu user prompt (dùng {{biến}})')->rows(3)->columnSpanFull(),
        ];
    }

    /** wire:click từ blade — bật/tắt guardrail. */
    public function toggleGuardrail(int $id): void
    {
        $g = AiGuardrailPolicy::find($id);
        if (! $g) {
            return;
        }
        $g->update(['is_active' => ! $g->is_active]);
        $this->audit('ai.guardrail_toggle', ($g->is_active ? 'Bật' : 'Tắt').' guardrail: '.$g->name, AiGuardrailPolicy::class, $g->id);
        Notification::make()->title($g->is_active ? 'Đã bật guardrail' : 'Đã tắt guardrail')->success()->send();
    }
}
