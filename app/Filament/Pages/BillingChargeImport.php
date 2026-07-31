<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\ImportsBillingChargesFromExcel;
use App\Models\AuditLog;
use App\Models\ImportBatch;
use App\Support\Context\CurrentContext;
use App\Support\Import\Profiles\BillingChargeImportProfile;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * BQL-07-08bis — Nhập khoản phí (kế toán), Phase B1 (`docs/BILLING_IMPORT_SPEC_20260731.md`).
 *
 * Cố ý MỎNG: upload + xem trước + ghi dùng `ImportsBillingChargesFromExcel` (khuôn
 * `ImportsResidentsFromExcel`); theo dõi chi tiết dòng / retry / export chung với mọi
 * loại import khác ở màn có sẵn "Nhật ký Import/Export". Bảng ở đây CHỈ hiện batch
 * `billing_charges` + hành động "Hoàn tác" riêng của miền này (spec §5.7) — generic
 * `StagingImporter` không biết gì về "bảng kê còn pending hay không".
 */
class BillingChargeImport extends Page implements HasTable
{
    use ImportsBillingChargesFromExcel;
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-arrow-up';

    protected static string|\UnitEnum|null $navigationGroup = 'Hóa đơn & thanh toán';

    protected static ?string $navigationLabel = 'Nhập khoản phí';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Nhập khoản phí (kế toán)';

    protected static ?string $slug = 'billing-charge-import';

    protected string $view = 'filament.pages.billing-charge-import';

    private function buildingIds(): array
    {
        return app(CurrentContext::class)->buildingIds() ?: [0];
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->billingChargeImportAction(),
            Action::make('goToHistory')
                ->label('Nhật ký Import/Export')
                ->icon('heroicon-m-clock')
                ->color('gray')
                ->url(url('/admin/import-history')),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => ImportBatch::query()
                ->whereIn('building_id', $this->buildingIds())
                ->where('import_type', 'billing_charges')
                ->with('createdBy')
                ->latest())
            ->columns([
                TextColumn::make('created_at')->label('Thời gian')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('file_name')->label('File nguồn')->limit(40),
                TextColumn::make('status')->label('Trạng thái')->badge()
                    ->formatStateUsing(fn (string $s): string => self::STATUS[$s][0] ?? $s)
                    ->color(fn (string $s): string => self::STATUS[$s][1] ?? 'gray'),
                TextColumn::make('total_rows')->label('Tổng')->alignRight(),
                TextColumn::make('valid_rows')->label('Hợp lệ')->alignRight()->color('success'),
                TextColumn::make('error_rows')->label('Lỗi')->alignRight()->color('danger'),
                TextColumn::make('createdBy.name')->label('Người nhập')->placeholder('—'),
                TextColumn::make('rolled_back_at')->label('Đã hoàn tác')->dateTime('d/m/Y H:i')->placeholder('—'),
            ])
            ->recordActions([
                Action::make('rollback')
                    ->label('Hoàn tác')
                    ->icon('heroicon-m-arrow-uturn-left')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Hoàn tác lô nhập khoản phí')
                    ->modalDescription('Chỉ hoàn tác được khi TẤT CẢ bảng kê liên quan còn "chờ duyệt". Đã phát hành thì phải dùng điều chỉnh riêng, không hoàn tác được.')
                    ->schema([
                        Textarea::make('reason')->label('Lý do hoàn tác')->required()->rows(2),
                    ])
                    ->visible(fn (ImportBatch $record): bool => $record->status === 'committed' && $record->rolled_back_at === null)
                    ->action(function (ImportBatch $record, array $data): void {
                        try {
                            $count = (new BillingChargeImportProfile)->rollbackBatch($record, auth()->id());
                            $this->audit('billing_charge.rollback', "Hoàn tác {$count} dòng phí từ file {$record->file_name}: {$data['reason']}");
                            Notification::make()->title("Đã hoàn tác {$count} dòng")->success()->send();
                        } catch (\RuntimeException $e) {
                            Notification::make()->title('Không thể hoàn tác')->body($e->getMessage())->danger()->send();
                        }
                        $this->resetTable();
                    }),
            ])
            ->emptyStateHeading('Chưa có lô nhập khoản phí nào')
            ->emptyStateIcon('heroicon-o-document-arrow-up')
            ->paginated([10, 25, 50]);
    }

    private const STATUS = [
        'uploaded' => ['Đã tải lên', 'gray'],
        'validated' => ['Đã kiểm tra', 'info'],
        'committing' => ['Đang ghi (nền)', 'warning'],
        'committed' => ['Hoàn tất', 'success'],
        'failed' => ['Thất bại', 'danger'],
        'cancelled' => ['Đã hủy', 'gray'],
    ];

    private function audit(string $action, string $description): void
    {
        $user = auth()->user();
        AuditLog::create([
            'tenant_id' => $user->tenant_id,
            'building_id' => $user->building_id,
            'user_id' => $user->id,
            'actor_name' => $user->name,
            'action' => $action,
            'description' => $description,
        ]);
    }
}
