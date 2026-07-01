<?php

namespace App\Filament\Pages;

use App\Enums\WorkOrderStatus;
use App\Filament\Concerns\ProvidesAiContext;
use App\Filament\Concerns\WritesAudit;
use App\Models\Team;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderAssignment;
use App\Models\WorkOrderChecklistItem;
use App\Models\WorkOrderSignature;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * BQL-2 — Bảng công việc Kanban (WEB-05 / QL-WO-01..04). Kéo-thả đổi trạng thái,
 * chi tiết + checklist + đính kèm + nghiệm thu (chữ ký). Scope theo dự án (CurrentContext).
 */
class WorkOrderKanban extends Page
{
    use ProvidesAiContext;
    use WritesAudit;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string|\UnitEnum|null $navigationGroup = 'Vận hành';

    protected static ?string $navigationLabel = 'Công việc (Kanban)';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Bảng công việc';

    protected static ?string $slug = 'work-orders/kanban';

    protected string $view = 'filament.pages.work-order-kanban';

    /** @return \Illuminate\Database\Eloquent\Builder<WorkOrder> */
    private function scoped()
    {
        $buildingIds = app(CurrentContext::class)->buildingIds() ?: [0];

        return WorkOrder::query()->whereIn('building_id', $buildingIds);
    }

    protected function getViewData(): array
    {
        $columns = [];
        foreach (WorkOrderStatus::cases() as $st) {
            $items = (clone $this->scoped())->where('status', $st->value)
                ->with(['assignee', 'checklists.items'])->latest('created_at')->limit(50)->get();
            $columns[] = ['status' => $st->value, 'label' => $st->label(), 'tone' => $st->tone(), 'items' => $items, 'count' => $items->count()];
        }

        $this->shareAiContext([
            'title' => 'Điều phối công việc',
            'lines' => ['Đang có '.(clone $this->scoped())->where('status', 'overdue')->count().' công việc quá hạn, '.(clone $this->scoped())->where('status', 'in_progress')->count().' đang xử lý.'],
        ]);

        return ['columns' => $columns];
    }

    /** Kéo-thả: đổi trạng thái công việc. */
    public function moveCard(int $id, string $status): void
    {
        if (! in_array($status, array_map(fn ($c) => $c->value, WorkOrderStatus::cases()), true)) {
            return;
        }
        $wo = (clone $this->scoped())->find($id);
        if (! $wo) {
            return;
        }
        $patch = ['status' => $status];
        if ($status === 'in_progress' && ! $wo->started_at) {
            $patch['started_at'] = now();
        }
        if ($status === 'done' && ! $wo->completed_at) {
            $patch['completed_at'] = now();
        }
        $wo->update($patch);
        $this->audit('work_order.move', 'Chuyển '.$wo->code.' → '.(WorkOrderStatus::from($status)->label()), WorkOrder::class, $wo->id);
        Notification::make()->title('Đã cập nhật '.$wo->code)->success()->send();
    }

    /** @return array<int, \Filament\Forms\Components\Component> */
    private function woById(array $arguments): ?WorkOrder
    {
        return (clone $this->scoped())->with(['checklists.items', 'attachments', 'signatures', 'assignments.assignee', 'assignee', 'apartment'])->find($arguments['id'] ?? 0);
    }

    public function detailWorkOrderAction(): Action
    {
        return Action::make('detailWorkOrder')
            ->modalHeading(fn (array $arguments) => ($this->woById($arguments)?->code ?? '').' — công việc')
            ->modalContent(fn (array $arguments) => view('filament.pages.work-order-detail', ['record' => $this->woById($arguments)]))
            ->modalSubmitAction(false)->modalCancelActionLabel('Đóng');
    }

    public function assignWorkOrderAction(): Action
    {
        return Action::make('assignWorkOrder')
            ->modalHeading('Giao việc')
            ->schema([
                Select::make('assigned_to_id')->label('Người xử lý')->required()->searchable()->options(fn () => User::where('account_type', 'staff')->pluck('name', 'id')),
                Select::make('team_id')->label('Tổ/đội')->options(fn () => Team::pluck('name', 'id')),
                Textarea::make('note')->label('Ghi chú')->rows(2),
            ])
            ->action(function (array $arguments, array $data): void {
                $wo = $this->woById($arguments);
                if (! $wo) {
                    return;
                }
                $wo->update(['assigned_to_id' => $data['assigned_to_id'], 'team_id' => $data['team_id'] ?? $wo->team_id]);
                WorkOrderAssignment::create(['work_order_id' => $wo->id, 'assigned_to_id' => $data['assigned_to_id'], 'assigned_by_id' => auth()->id(), 'team_id' => $data['team_id'] ?? null, 'role' => 'primary', 'status' => 'assigned', 'assigned_at' => now()]);
                $this->audit('work_order.assign', 'Giao việc '.$wo->code, WorkOrder::class, $wo->id);
                Notification::make()->title('Đã giao việc')->success()->send();
            });
    }

    public function checklistWorkOrderAction(): Action
    {
        return Action::make('checklistWorkOrder')
            ->modalHeading('Cập nhật checklist')
            ->fillForm(fn (array $arguments) => ['done' => $this->woById($arguments)?->checklists->flatMap->items->where('is_done', true)->pluck('id')->all() ?? []])
            ->schema(fn (array $arguments) => [
                CheckboxList::make('done')->label('Đánh dấu mục đã hoàn thành')
                    ->options(fn () => $this->woById($arguments)?->checklists->flatMap->items->pluck('label', 'id')->all() ?? []),
            ])
            ->action(function (array $arguments, array $data): void {
                $wo = $this->woById($arguments);
                if (! $wo) {
                    return;
                }
                $doneIds = $data['done'] ?? [];
                foreach ($wo->checklists->flatMap->items as $item) {
                    $isDone = in_array($item->id, $doneIds);
                    if ($item->is_done !== $isDone) {
                        $item->update(['is_done' => $isDone, 'done_by_id' => $isDone ? auth()->id() : null, 'done_at' => $isDone ? now() : null]);
                    }
                }
                $this->audit('work_order.checklist', 'Cập nhật checklist '.$wo->code, WorkOrder::class, $wo->id);
                Notification::make()->title('Đã cập nhật checklist')->success()->send();
            });
    }

    public function signoffWorkOrderAction(): Action
    {
        return Action::make('signoffWorkOrder')
            ->modalHeading('Nghiệm thu công việc')
            ->schema([
                TextInput::make('signer_name')->label('Người ký nghiệm thu')->required()->default(fn () => auth()->user()->name),
                Select::make('signer_role')->label('Vai trò')->options(['technician' => 'Kỹ thuật', 'supervisor' => 'Giám sát', 'resident' => 'Cư dân'])->default('supervisor')->required(),
                Textarea::make('note')->label('Ghi chú nghiệm thu')->rows(2),
            ])
            ->action(function (array $arguments, array $data): void {
                $wo = $this->woById($arguments);
                if (! $wo) {
                    return;
                }
                WorkOrderSignature::create(['work_order_id' => $wo->id, 'signer_name' => $data['signer_name'], 'signer_role' => $data['signer_role'], 'signed_at' => now()]);
                $wo->update(['status' => 'done', 'completed_at' => $wo->completed_at ?? now()]);
                $this->audit('work_order.signoff', 'Nghiệm thu '.$wo->code, WorkOrder::class, $wo->id);
                Notification::make()->title('Đã nghiệm thu '.$wo->code)->success()->send();
            });
    }
}
