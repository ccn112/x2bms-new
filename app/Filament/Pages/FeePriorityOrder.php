<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\BillingFamily;
use App\Models\FeeType;
use App\Models\FeeTypePriorityOverride;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

/**
 * Phase B4 (D4-bis) — kéo-thả sắp THỨ TỰ ƯU TIÊN PHÂN BỔ riêng cho dự án đang chọn.
 *
 * Nằm cạnh `FeeCatalog` (danh mục phí, cấp TENANT) nhưng đây là màn khác việc: sửa
 * `fee_types.payment_priority` ở đây sẽ đổi cho MỌI dự án của công ty — cái D4 cần là
 * "dự án A ưu tiên khác dự án B". Nên màn này thao tác trên bảng riêng
 * `fee_type_priority_overrides` (khoá bởi `project_id`), KHÔNG đụng `fee_types`.
 *
 * Dự án đang thao tác = workspace hiện hành (`CurrentContext::projectId()`) — đúng mô
 * hình "BQL làm việc trong MỘT dự án" đã dùng khắp `/admin` (xem docblock
 * `CurrentContext`), không cần thêm bộ chọn dự án ở màn này.
 *
 * Chưa tuỳ chỉnh: bảng rỗng + nút "Khởi tạo từ mặc định" (copy thứ tự tenant-wide hiện
 * tại thành điểm bắt đầu để kéo-thả). Đã tuỳ chỉnh: kéo-thả ghi thẳng
 * `payment_priority` của TỪNG override qua `->reorderable()` (Filament tự xử lý), đọc
 * lại ở `StatementLine::effectivePaymentPriority()`.
 */
class FeePriorityOrder extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrows-up-down';

    protected static string|\UnitEnum|null $navigationGroup = 'Tài chính – Phí';

    protected static ?string $navigationLabel = 'Thứ tự ưu tiên';

    protected static ?int $navigationSort = 11;

    protected static ?string $title = 'Thứ tự ưu tiên phân bổ tiền';

    protected static ?string $slug = 'fees/priority';

    protected string $view = 'filament.pages.fee-priority-order';

    private function projectId(): ?int
    {
        return app(CurrentContext::class)->projectId();
    }

    private function tenantId(): ?int
    {
        return app(CurrentContext::class)->tenantId();
    }

    private function hasOverrides(): bool
    {
        $projectId = $this->projectId();

        return $projectId !== null
            && FeeTypePriorityOverride::withoutGlobalScopes()->where('project_id', $projectId)->exists();
    }

    protected function getViewData(): array
    {
        return [
            'projectName' => app(CurrentContext::class)->project()?->name ?? '—',
            'hasOverrides' => $this->hasOverrides(),
        ];
    }

    /**
     * Tạo dòng override cho MỌI fee_type đang active của tenant, priority ban đầu =
     * đúng thứ tự tenant-wide hiện tại (`fee_types.payment_priority` tăng dần, đã
     * backfill theo family) — điểm bắt đầu để BQL kéo-thả sắp lại riêng cho dự án,
     * không phải một bộ số ngẫu nhiên.
     */
    private function initializeFromDefault(): void
    {
        $projectId = $this->projectId();
        $tenantId = $this->tenantId();
        if ($projectId === null || $tenantId === null) {
            return;
        }

        $feeTypes = FeeType::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->orderBy('payment_priority')
            ->orderBy('id')
            ->get(['id']);

        DB::transaction(function () use ($feeTypes, $tenantId, $projectId): void {
            $rank = 1;
            foreach ($feeTypes as $feeType) {
                FeeTypePriorityOverride::withoutGlobalScopes()->updateOrCreate(
                    ['project_id' => $projectId, 'fee_type_id' => $feeType->id],
                    ['tenant_id' => $tenantId, 'payment_priority' => $rank],
                );
                $rank++;
            }
        });
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('initFromDefault')
                ->label('Khởi tạo từ mặc định')
                ->icon('heroicon-m-sparkles')
                ->color('primary')
                ->visible(fn () => $this->projectId() !== null && ! $this->hasOverrides())
                ->requiresConfirmation()
                ->modalHeading('Khởi tạo thứ tự riêng cho dự án này')
                ->modalDescription('Tạo danh sách bắt đầu từ thứ tự mặc định hiện tại của công ty. Sau đó kéo-thả để sắp lại — chỉ dự án này bị ảnh hưởng.')
                ->action(function (): void {
                    $this->initializeFromDefault();
                    $this->resetTable();
                    Notification::make()->title('Đã khởi tạo — kéo (⠿) để sắp lại')->success()->send();
                }),
            Action::make('resetToDefault')
                ->label('Khôi phục mặc định công ty')
                ->icon('heroicon-m-arrow-uturn-left')
                ->color('danger')
                ->visible(fn () => $this->hasOverrides())
                ->requiresConfirmation()
                ->modalHeading('Xoá tuỳ chỉnh của dự án này')
                ->modalDescription('Xoá toàn bộ thứ tự riêng đã sắp cho dự án này — quay lại dùng đúng mặc định tenant-wide.')
                ->action(function (): void {
                    FeeTypePriorityOverride::withoutGlobalScopes()->where('project_id', $this->projectId())->delete();
                    $this->resetTable();
                    Notification::make()->title('Đã khôi phục mặc định')->success()->send();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        $projectId = $this->projectId();

        return $table
            ->query(
                FeeTypePriorityOverride::withoutGlobalScopes()
                    ->where('project_id', $projectId ?? 0)
                    ->with('feeType')
            )
            ->defaultSort('payment_priority')
            ->reorderable('payment_priority')
            ->columns([
                TextColumn::make('feeType.name')->label('Loại phí')->weight('medium'),
                TextColumn::make('feeType.code')->label('Mã')->badge()->color('gray'),
                TextColumn::make('family')->label('Nhóm phí')->badge()
                    ->getStateUsing(fn (FeeTypePriorityOverride $r) => $r->feeType ? BillingFamily::fromFeeType($r->feeType)->label() : '—')
                    ->color(fn (FeeTypePriorityOverride $r) => match ($r->feeType ? BillingFamily::fromFeeType($r->feeType) : null) {
                        BillingFamily::Management => 'blue',
                        BillingFamily::Water => 'cyan',
                        BillingFamily::Electricity => 'amber',
                        BillingFamily::Vehicle => 'purple',
                        default => 'gray',
                    }),
                IconColumn::make('feeType.is_critical')->label('Bắt buộc')->boolean(),
                TextColumn::make('payment_priority')->label('Thứ tự hiện tại')->numeric(),
            ])
            ->emptyStateHeading('Dự án này đang dùng thứ tự mặc định của công ty')
            ->emptyStateDescription('Chưa có tuỳ chỉnh riêng. Bấm "Khởi tạo từ mặc định" phía trên để bắt đầu kéo-thả.')
            ->emptyStateIcon('heroicon-o-arrows-up-down')
            ->paginated(false);
    }
}
