<?php

namespace App\Filament\Pages;

use App\Jobs\CommitImportBatchJob;
use App\Models\ImportBatch;
use App\Models\Resident;
use App\Support\Context\CurrentContext;
use App\Support\Export\ExportsCsv;
use App\Support\Storage\TenantStorage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Nhật ký Import/Export — theo dõi các lần nhập liệu (import_batches): trạng thái,
 * số dòng, người tạo, tải/xem file nguồn, **nhập lại (retry)** dòng còn lại, và
 * **export kết quả** để đối chiếu. Scope theo tòa của BQL.
 */
class ImportHistory extends Page implements HasTable
{
    use ExportsCsv;
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static string|\UnitEnum|null $navigationGroup = 'Cư dân & Căn hộ';

    protected static ?string $navigationLabel = 'Nhật ký Import/Export';

    protected static ?int $navigationSort = 20;

    protected static ?string $title = 'Nhật ký Import/Export';

    protected static ?string $slug = 'import-history';

    protected string $view = 'filament.pages.import-history';

    private const STATUS = [
        'uploaded' => ['Đã tải lên', 'gray'],
        'mapped' => ['Đã map', 'gray'],
        'validated' => ['Đã kiểm tra', 'info'],
        'committing' => ['Đang ghi (nền)', 'warning'],
        'committed' => ['Hoàn tất', 'success'],
        'failed' => ['Thất bại', 'danger'],
        'cancelled' => ['Đã hủy', 'gray'],
    ];

    private const IMPORT_TYPE = [
        'residents' => 'Cư dân/Căn hộ',
        'projects_employees' => 'Dự án/Nhân sự',
    ];

    private function buildingIds(): array
    {
        return app(CurrentContext::class)->buildingIds() ?: [0];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => ImportBatch::query()
                ->whereIn('building_id', $this->buildingIds())
                ->with('createdBy')
                ->latest())
            ->columns([
                TextColumn::make('created_at')->label('Thời gian')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('file_name')->label('File nguồn')->limit(40)->searchable(),
                TextColumn::make('import_type')->label('Loại')
                    ->formatStateUsing(fn (string $state): string => self::IMPORT_TYPE[$state] ?? $state)->badge()->color('gray'),
                TextColumn::make('status')->label('Trạng thái')->badge()
                    ->formatStateUsing(fn (string $state): string => self::STATUS[$state][0] ?? $state)
                    ->color(fn (string $state): string => self::STATUS[$state][1] ?? 'gray'),
                TextColumn::make('total_rows')->label('Tổng')->alignRight(),
                TextColumn::make('valid_rows')->label('Hợp lệ')->alignRight()->color('success'),
                TextColumn::make('error_rows')->label('Lỗi')->alignRight()->color('danger'),
                TextColumn::make('createdBy.name')->label('Người tạo')->placeholder('—'),
                TextColumn::make('committed_at')->label('Ghi lúc')->dateTime('d/m/Y H:i')->placeholder('—'),
            ])
            ->recordActions([
                Action::make('details')->label('Chi tiết')->icon('heroicon-m-eye')->color('gray')
                    ->modalHeading('Chi tiết dòng import')->modalWidth('4xl')->modalSubmitAction(false)->modalCancelActionLabel('Đóng')
                    ->modalContent(fn (ImportBatch $record): HtmlString => $this->detailContent($record)),
                Action::make('downloadSource')->label('Tải file nguồn')->icon('heroicon-m-arrow-down-tray')->color('gray')
                    ->visible(fn (ImportBatch $record): bool => filled($record->storage_path) && app(TenantStorage::class)->exists($record->storage_path))
                    ->action(fn (ImportBatch $record): StreamedResponse => app(TenantStorage::class)->download($record->storage_path, $record->file_name)),
                Action::make('retry')->label('Nhập lại dòng còn lại')->icon('heroicon-m-arrow-path')->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription('Ghi lại các dòng hợp lệ chưa được nhập (dòng đã nhập sẽ bỏ qua — an toàn, không tạo trùng).')
                    ->visible(fn (ImportBatch $record): bool => in_array($record->status, ['failed', 'committed', 'validated'], true) && (int) $record->valid_rows > 0)
                    ->action(function (ImportBatch $record): void {
                        CommitImportBatchJob::dispatch($record->id, [
                            'tenant_id' => $record->tenant_id,
                            'building_id' => $record->building_id,
                            'user_id' => auth()->id(),
                        ]);
                        $record->update(['status' => 'committing']);
                        Notification::make()->title('Đã đưa vào hàng đợi nhập lại')->success()->send();
                    }),
                Action::make('exportResult')->label('Export kết quả')->icon('heroicon-m-arrow-up-tray')->color('gray')
                    ->visible(fn (ImportBatch $record): bool => $record->import_type === 'residents' && (int) $record->error_rows >= 0)
                    ->action(fn (ImportBatch $record): StreamedResponse => $this->exportBatchResult($record)),
            ]);
    }

    /** Bảng chi tiết từng dòng của batch (tối đa 300 dòng). */
    private function detailContent(ImportBatch $batch): HtmlString
    {
        $rows = $batch->rows()->orderBy('row_number')->limit(300)->get();
        $body = '';
        foreach ($rows as $r) {
            $p = $r->normalized_payload ?? [];
            $tone = match ($r->validation_status) {
                'error' => '#e11d48', 'warning' => '#d97706', 'imported' => '#059669', default => '#475569',
            };
            $notes = collect($r->validation_errors ?? [])->map(fn ($i) => e($i['message'] ?? ''))->implode('<br>');
            $body .= '<tr style="border-top:1px solid #e2e8f0;">'
                .'<td style="padding:6px 8px;color:#64748b;">'.e((string) $r->row_number).'</td>'
                .'<td style="padding:6px 8px;">'.e((string) ($p['full_name'] ?? '')).'</td>'
                .'<td style="padding:6px 8px;">'.e((string) ($p['id_no'] ?? '')).'</td>'
                .'<td style="padding:6px 8px;">'.e((string) ($p['phone'] ?? '')).'</td>'
                .'<td style="padding:6px 8px;white-space:nowrap;color:'.$tone.';font-weight:600;">'.e($r->validation_status).'</td>'
                .'<td style="padding:6px 8px;color:#64748b;font-size:12px;">'.$notes.'</td></tr>';
        }

        return new HtmlString(
            '<div style="max-height:56vh;overflow:auto;border:1px solid #e2e8f0;border-radius:8px;">'
            .'<table style="width:100%;border-collapse:collapse;font-size:13px;">'
            .'<thead><tr style="background:#f8fafc;text-align:left;">'
            .'<th style="padding:6px 8px;">Dòng</th><th style="padding:6px 8px;">Họ tên</th><th style="padding:6px 8px;">CCCD</th>'
            .'<th style="padding:6px 8px;">SĐT</th><th style="padding:6px 8px;">Trạng thái</th><th style="padding:6px 8px;">Ghi chú</th>'
            .'</tr></thead><tbody>'.$body.'</tbody></table></div>'
        );
    }

    /** Export CSV các cư dân đã tạo bởi batch này (đối chiếu dữ liệu đã lưu). */
    private function exportBatchResult(ImportBatch $batch): StreamedResponse
    {
        $ids = $batch->rows()
            ->where('committed_entity_type', Resident::class)
            ->whereNotNull('committed_entity_id')
            ->pluck('committed_entity_id');

        $rows = Resident::query()->whereIn('id', $ids)
            ->with(['building', 'apartmentRelations.apartment'])->orderBy('code')->get();

        return $this->streamCsv(
            $rows,
            ['Mã CD', 'Họ tên', 'CCCD', 'SĐT', 'Email', 'Tòa', 'Căn hộ', 'Trạng thái hồ sơ'],
            fn (Resident $r): array => [
                $r->code, $r->full_name, $r->id_no, $r->phone, $r->email, $r->building?->name,
                $r->apartmentRelations->first()?->apartment?->code ?? '',
                $r->profile_status,
            ],
            'ket_qua_import_'.$batch->id,
        );
    }
}
