<?php

namespace App\Filament\Pages;

use App\Enums\FeedbackStatus;
use App\Filament\Concerns\ProvidesAiContext;
use App\Filament\Concerns\WritesAudit;
use App\Models\AuditLog;
use App\Models\FeedbackAssignment;
use App\Models\FeedbackComment;
use App\Models\FeedbackRequest;
use App\Models\FeedbackStatusHistory;
use App\Models\Team;
use App\Models\User;
use App\Models\WorkOrder;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

/**
 * BQL-1 — Hàng đợi & xử lý phản ánh (QL-FB-01..03). Queue theo dự án (CurrentContext),
 * KPI + phân bố theo danh mục, chi tiết + timeline (comment/assignment/status history),
 * điều phối: giao việc, chuyển trạng thái, tạo công việc, đóng + đánh giá.
 */
class FeedbackQueue extends Page implements HasTable
{
    use InteractsWithTable;
    use ProvidesAiContext;
    use WritesAudit;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string|\UnitEnum|null $navigationGroup = 'Vận hành';

    protected static ?string $navigationLabel = 'Phản ánh';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Hàng đợi phản ánh';

    protected static ?string $slug = 'feedback/queue';

    protected string $view = 'filament.pages.feedback-queue';

    /** @return \Illuminate\Database\Eloquent\Builder<FeedbackRequest> */
    private function scoped()
    {
        $buildingIds = app(CurrentContext::class)->buildingIds() ?: [0];

        return FeedbackRequest::query()->whereIn('building_id', $buildingIds);
    }

    /** tone (x2) → màu Filament badge. */
    private function toneColor(string $tone): string
    {
        return ['red' => 'danger', 'amber' => 'warning', 'blue' => 'info', 'green' => 'success', 'slate' => 'gray'][$tone] ?? 'gray';
    }

    protected function getViewData(): array
    {
        $pending = FeedbackStatus::pendingValues();
        $overdue = (clone $this->scoped())->whereIn('status', $pending)
            ->whereNotNull('sla_due_at')->where('sla_due_at', '<', now())->count();

        $byCat = (clone $this->scoped())->selectRaw('feedback_category_id, count(*) as c')
            ->groupBy('feedback_category_id')->pluck('c', 'feedback_category_id');
        $cats = \App\Models\FeedbackCategory::whereIn('id', $byCat->keys()->filter())->get()
            ->map(fn ($c) => ['name' => $c->name, 'color' => $c->color, 'count' => $byCat[$c->id] ?? 0])
            ->sortByDesc('count')->values();

        $topPending = (clone $this->scoped())->whereIn('status', $pending)
            ->orderByRaw('sla_due_at is null, sla_due_at asc')->limit(3)->get();

        $this->shareAiContext([
            'title' => 'Gợi ý xử lý phản ánh',
            'lines' => [$overdue > 0 ? "Có {$overdue} phản ánh QUÁ HẠN SLA cần ưu tiên." : 'Không có phản ánh quá hạn.'],
            'suggestions' => $topPending->map(fn (FeedbackRequest $r) => [
                'title' => $r->code ?? ('#'.$r->id),
                'sub' => \Illuminate\Support\Str::limit($r->title, 40),
            ])->all(),
        ]);

        return [
            'kpis' => [
                ['label' => 'Chờ xử lý', 'value' => (clone $this->scoped())->whereIn('status', $pending)->count(), 'accent' => 'amber'],
                ['label' => 'Quá hạn SLA', 'value' => $overdue, 'accent' => 'red'],
                ['label' => 'Đã xử lý', 'value' => (clone $this->scoped())->where('status', FeedbackStatus::Resolved->value)->count(), 'accent' => 'green'],
                ['label' => 'Đã đóng', 'value' => (clone $this->scoped())->where('status', FeedbackStatus::Closed->value)->count(), 'accent' => 'blue'],
            ],
            'categories' => $cats,
            'catMax' => max(1, (int) $cats->max('count')),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->scoped()->with(['category', 'apartment', 'assignee', 'resident']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('code')->label('Mã')->searchable()->placeholder('—')->color('primary')
                    ->description(fn (FeedbackRequest $r) => \Illuminate\Support\Str::limit($r->title, 48)),
                TextColumn::make('category.name')->label('Danh mục')->badge()->color('gray')->placeholder('—'),
                TextColumn::make('apartment.code')->label('Căn hộ')->placeholder('—'),
                TextColumn::make('channel')->label('Kênh')->badge()->color('gray')->toggleable(),
                TextColumn::make('priority')->label('Ưu tiên')->badge()
                    ->color(fn (string $state) => ['urgent' => 'danger', 'high' => 'warning', 'normal' => 'gray', 'low' => 'gray'][$state] ?? 'gray'),
                TextColumn::make('status')->label('Trạng thái')->badge()
                    ->formatStateUsing(fn (FeedbackStatus $state) => $state->label())
                    ->color(fn (FeedbackStatus $state) => $this->toneColor($state->tone())),
                TextColumn::make('assignee.name')->label('Người xử lý')->placeholder('Chưa giao'),
                TextColumn::make('sla_due_at')->label('SLA')
                    ->formatStateUsing(fn ($state) => $state ? $state->diffForHumans() : '—')
                    ->color(fn (FeedbackRequest $r) => $r->sla_due_at && $r->sla_due_at->isPast() && in_array($r->status->value, FeedbackStatus::pendingValues(), true) ? 'danger' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('status')->label('Trạng thái')
                    ->options(collect(FeedbackStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all()),
                SelectFilter::make('priority')->label('Ưu tiên')
                    ->options(['urgent' => 'Khẩn', 'high' => 'Cao', 'normal' => 'Thường', 'low' => 'Thấp']),
                SelectFilter::make('feedback_category_id')->label('Danh mục')->relationship('category', 'name'),
            ])
            ->recordActions([
                $this->viewAction(),
                Action::make('comment')
                    ->label('Trao đổi')->iconButton()->icon('heroicon-m-chat-bubble-oval-left')->color('gray')
                    ->schema([Textarea::make('body')->label('Nội dung')->required()->rows(3)])
                    ->action(function (FeedbackRequest $r, array $data): void {
                        FeedbackComment::create(['feedback_request_id' => $r->id, 'user_id' => auth()->id(), 'author_name' => auth()->user()->name, 'body' => $data['body'], 'is_internal' => true]);
                        $this->audit('feedback.comment', 'Ghi chú phản ánh '.($r->code ?? $r->id), FeedbackRequest::class, $r->id);
                        Notification::make()->title('Đã thêm trao đổi')->success()->send();
                    }),
                Action::make('assign')
                    ->label('Giao việc')->iconButton()->icon('heroicon-m-user-plus')->color('info')
                    ->schema([
                        Select::make('assigned_to_id')->label('Người xử lý')->required()->searchable()
                            ->options(fn () => User::where('account_type', 'staff')->pluck('name', 'id')),
                        Select::make('team_id')->label('Tổ/đội')->options(fn () => Team::pluck('name', 'id')),
                        Textarea::make('note')->label('Ghi chú')->rows(2),
                    ])
                    ->action(fn (FeedbackRequest $r, array $data) => $this->assign($r, $data)),
                Action::make('createWorkOrder')
                    ->label('Tạo công việc')->iconButton()->icon('heroicon-m-wrench-screwdriver')->color('gray')
                    ->requiresConfirmation()->modalHeading('Tạo công việc từ phản ánh')
                    ->action(fn (FeedbackRequest $r) => $this->createWorkOrder($r)),
                Action::make('start')
                    ->label('Bắt đầu xử lý')->iconButton()->icon('heroicon-m-play')->color('warning')
                    ->visible(fn (FeedbackRequest $r) => in_array($r->status->value, ['new', 'assigned'], true))
                    ->action(fn (FeedbackRequest $r) => $this->changeStatus($r, FeedbackStatus::InProgress)),
                Action::make('resolve')
                    ->label('Đã xử lý')->iconButton()->icon('heroicon-m-check')->color('success')
                    ->visible(fn (FeedbackRequest $r) => $r->status->value === 'in_progress')
                    ->action(fn (FeedbackRequest $r) => $this->changeStatus($r, FeedbackStatus::Resolved, ['resolved_at' => now()])),
                Action::make('close')
                    ->label('Đóng')->iconButton()->icon('heroicon-m-lock-closed')->color('gray')
                    ->visible(fn (FeedbackRequest $r) => $r->status->value === 'resolved')
                    ->schema([Select::make('rating')->label('Đánh giá (1-5)')->options([5 => '5 ★', 4 => '4', 3 => '3', 2 => '2', 1 => '1'])])
                    ->action(fn (FeedbackRequest $r, array $data) => $this->changeStatus($r, FeedbackStatus::Closed, ['closed_at' => now(), 'rating' => $data['rating'] ?? null])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('assignBulk')->label('Giao việc hàng loạt')->icon('heroicon-m-user-plus')
                        ->schema([Select::make('assigned_to_id')->label('Người xử lý')->required()->searchable()->options(fn () => User::where('account_type', 'staff')->pluck('name', 'id'))])
                        ->action(function (Collection $records, array $data): void {
                            $records->each(fn (FeedbackRequest $r) => $this->assign($r, $data, false));
                            Notification::make()->title('Đã giao '.$records->count().' phản ánh')->success()->send();
                        })->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->emptyStateHeading('Không có phản ánh')
            ->emptyStateIcon('heroicon-o-chat-bubble-left-right')
            ->striped()
            ->paginated([10, 25, 50]);
    }

    public function viewAction(): Action
    {
        return Action::make('view')
            ->label('Chi tiết')->iconButton()->icon('heroicon-m-eye')->color('primary')
            ->modalHeading(fn (FeedbackRequest $r) => ($r->code ? $r->code.' — ' : '').$r->title)
            ->modalContent(fn (FeedbackRequest $r) => view('filament.pages.feedback-detail', [
                'record' => $r->load(['category', 'apartment', 'resident', 'assignee', 'comments.user', 'attachments', 'assignments.assignee', 'statusHistories.changedBy']),
            ]))
            ->modalSubmitAction(false)->modalCancelActionLabel('Đóng');
    }

    private function assign(FeedbackRequest $r, array $data, bool $notify = true): void
    {
        $from = $r->status->value;
        $r->update(['assigned_to_id' => $data['assigned_to_id'], 'team_id' => $data['team_id'] ?? $r->team_id, 'status' => FeedbackStatus::Assigned->value]);
        FeedbackAssignment::create([
            'feedback_request_id' => $r->id, 'assigned_to_id' => $data['assigned_to_id'], 'assigned_by_id' => auth()->id(),
            'team_id' => $data['team_id'] ?? null, 'status' => 'assigned', 'note' => $data['note'] ?? null, 'assigned_at' => now(),
        ]);
        $this->logStatus($r, $from, FeedbackStatus::Assigned->value, $data['note'] ?? 'Giao việc');
        $this->audit('feedback.assign', 'Giao phản ánh '.($r->code ?? $r->id), FeedbackRequest::class, $r->id);
        if ($notify) {
            Notification::make()->title('Đã giao việc')->success()->send();
        }
    }

    private function changeStatus(FeedbackRequest $r, FeedbackStatus $to, array $extra = []): void
    {
        $from = $r->status->value;
        $r->update(['status' => $to->value] + $extra);
        $this->logStatus($r, $from, $to->value, null);
        $this->audit('feedback.status', $to->label().' phản ánh '.($r->code ?? $r->id), FeedbackRequest::class, $r->id);
        Notification::make()->title($to->label())->success()->send();
    }

    private function createWorkOrder(FeedbackRequest $r): void
    {
        $user = auth()->user();
        $wo = WorkOrder::create([
            'tenant_id' => $r->tenant_id, 'building_id' => $r->building_id, 'project_id' => $r->project_id,
            'apartment_id' => $r->apartment_id, 'feedback_request_id' => $r->id,
            'code' => 'WO-FB-'.$r->id, 'title' => 'Xử lý: '.$r->title, 'description' => $r->description,
            'status' => 'pending', 'priority' => $r->priority, 'assigned_to_id' => $r->assigned_to_id,
            'created_by_id' => $user->id, 'department_id' => null,
        ]);
        $this->audit('feedback.create_wo', 'Tạo công việc '.$wo->code.' từ phản ánh '.($r->code ?? $r->id), WorkOrder::class, $wo->id);
        Notification::make()->title('Đã tạo công việc '.$wo->code)->success()->send();
    }

    private function logStatus(FeedbackRequest $r, ?string $from, string $to, ?string $note): void
    {
        FeedbackStatusHistory::create([
            'feedback_request_id' => $r->id, 'from_status' => $from, 'to_status' => $to,
            'changed_by_id' => auth()->id(), 'note' => $note, 'changed_at' => now(),
        ]);
    }
}
