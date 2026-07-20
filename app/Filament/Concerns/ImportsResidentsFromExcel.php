<?php

namespace App\Filament\Concerns;

use App\Models\ImportBatch;
use App\Support\Context\CurrentContext;
use App\Support\Import\Profiles\ResidentImportProfile;
use App\Support\Import\StagingImporter;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Nhập cư dân từ Excel/CSV qua staging (StagingImporter + ResidentImportProfile):
 *   Bước 1 (residentImport): chọn tòa + tải file → stage() → mở modal xem trước.
 *   Bước 2 (residentImportPreview): xem đếm valid/error + bảng từng dòng → commit().
 *
 * Cả 2 auto-discover qua tên `*Action`. Dùng cho các Page BQL có bảng cư dân.
 * Page dùng trait phải có: `refreshTable()`, và `audit()` (đã có ở ResidentDirectory).
 */
trait ImportsResidentsFromExcel
{
    /** Bước 1 — upload + chọn tòa. */
    public function residentImportAction(): Action
    {
        return Action::make('residentImport')
            ->label('Nhập dữ liệu')
            ->icon('heroicon-m-arrow-up-tray')
            ->color('gray')
            ->modalHeading('Nhập cư dân từ Excel/CSV')
            ->modalDescription('Tải file .xlsx hoặc .csv. Cột bắt buộc: Họ tên, CCCD/CMND, SĐT. Hệ thống kiểm tra từng dòng trước khi ghi.')
            ->modalIcon('heroicon-o-arrow-up-tray')
            ->modalWidth('lg')
            ->modalSubmitActionLabel('Kiểm tra dữ liệu')
            ->schema([
                Select::make('building_id')
                    ->label('Tòa / dự án')
                    ->options(fn (): array => app(CurrentContext::class)->buildings()->pluck('name', 'id')->all())
                    ->required()
                    ->native(false),
                FileUpload::make('file')
                    ->label('File dữ liệu (.xlsx / .csv)')
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-excel',
                        'text/csv',
                        'text/plain',
                    ])
                    ->disk('local')
                    ->directory('imports/residents')
                    ->required(),
            ])
            ->action(function (array $data): void {
                $ctx = $this->residentImportContext((int) $data['building_id']);
                $path = Storage::disk('local')->path($data['file']);

                $batch = app(StagingImporter::class)->stage(
                    $path,
                    basename((string) $data['file']),
                    new ResidentImportProfile,
                    $ctx,
                    $data['file'],
                );

                $this->replaceMountedAction('residentImportPreview', ['batch' => $batch->id]);
            });
    }

    /** Tải file mẫu .xlsx sinh từ ĐÚNG cột của ResidentImportProfile (luôn khớp). */
    public function residentImportTemplateAction(): Action
    {
        return Action::make('residentImportTemplate')
            ->label('Tải file mẫu')
            ->icon('heroicon-m-document-arrow-down')
            ->color('gray')
            ->action(fn (): BinaryFileResponse => $this->downloadResidentImportTemplate());
    }

    public function downloadResidentImportTemplate(): BinaryFileResponse
    {
        $cols = (new ResidentImportProfile)->columns();

        // 2 dòng ví dụ: 1 đầy đủ, 1 tối thiểu (chỉ cột bắt buộc).
        $full = [];
        $min = [];
        foreach ($cols as $c) {
            $full[$c->label] = $c->example ?? '';
            $min[$c->label] = $c->required ? ($c->example ?? '') : '';
        }

        $dir = storage_path('app/tmp');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $path = $dir.'/mau_import_cu_dan_'.now()->format('His').'.xlsx';

        SimpleExcelWriter::create($path)
            ->addRow($full)
            ->addRow($min)
            ->close();

        return response()->download($path, 'mau_import_cu_dan.xlsx')->deleteFileAfterSend();
    }

    /** Bước 2 — xem trước kết quả validate + xác nhận ghi. */
    public function residentImportPreviewAction(): Action
    {
        return Action::make('residentImportPreview')
            ->modalHeading('Xem trước & xác nhận nhập cư dân')
            ->modalIcon('heroicon-o-clipboard-document-check')
            ->modalWidth('4xl')
            ->modalSubmitActionLabel('Ghi các dòng hợp lệ')
            ->modalContent(fn (array $arguments): HtmlString => $this->importPreviewContent($arguments))
            ->action(function (array $arguments): void {
                $batch = ImportBatch::findOrFail($arguments['batch']);

                if ((int) $batch->valid_rows === 0) {
                    Notification::make()->title('Không có dòng hợp lệ để ghi')->warning()->send();

                    return;
                }

                $summary = app(StagingImporter::class)->commit(
                    $batch,
                    new ResidentImportProfile,
                    $this->residentImportContext((int) $batch->building_id),
                );

                if (method_exists($this, 'audit')) {
                    $this->audit('resident.import', "Nhập cư dân từ file {$batch->file_name}: tạo {$summary->created}, bỏ qua {$summary->skipped}.");
                }

                Notification::make()
                    ->title('Đã nhập '.$summary->created.' cư dân')
                    ->body($summary->skipped > 0 ? $summary->skipped.' dòng lỗi bị bỏ qua.' : null)
                    ->success()->send();

                if (method_exists($this, 'refreshTable')) {
                    $this->refreshTable();
                }
            });
    }

    /** @return array{tenant_id:int, building_id:int, user_id:int|null} */
    protected function residentImportContext(int $buildingId): array
    {
        $user = auth()->user();

        return [
            'tenant_id' => $user->tenant_id,
            'building_id' => $buildingId,
            'user_id' => $user->id,
        ];
    }

    /** Bảng xem trước: đếm + từng dòng (tối đa 200 dòng hiển thị). */
    private function importPreviewContent(array $arguments): HtmlString
    {
        $batch = ImportBatch::with(['rows' => fn ($q) => $q->orderBy('row_number')])->findOrFail($arguments['batch']);

        $summary = '<div class="flex gap-3 mb-4 text-sm">'
            .$this->previewStat('Tổng dòng', (int) $batch->total_rows, 'slate')
            .$this->previewStat('Hợp lệ', (int) $batch->valid_rows, 'emerald')
            .$this->previewStat('Lỗi', (int) $batch->error_rows, 'rose')
            .'</div>';

        $rows = '';
        foreach ($batch->rows as $r) {
            $p = $r->normalized_payload ?? [];
            $isError = $r->validation_status === 'error';
            $badge = $isError
                ? '<span style="color:#e11d48;font-weight:600;">● Lỗi</span>'
                : ($r->validation_status === 'warning'
                    ? '<span style="color:#d97706;font-weight:600;">● Cảnh báo</span>'
                    : '<span style="color:#059669;font-weight:600;">● Hợp lệ</span>');

            $notes = collect($r->validation_errors ?? [])->map(fn ($i) => e($i['message'] ?? ''))->implode('<br>');

            $rows .= '<tr style="border-top:1px solid #e2e8f0;">'
                .'<td style="padding:6px 8px;color:#64748b;">'.e((string) $r->row_number).'</td>'
                .'<td style="padding:6px 8px;">'.e((string) ($p['full_name'] ?? '')).'</td>'
                .'<td style="padding:6px 8px;">'.e((string) ($p['id_no'] ?? '')).'</td>'
                .'<td style="padding:6px 8px;">'.e((string) ($p['phone'] ?? '')).'</td>'
                .'<td style="padding:6px 8px;white-space:nowrap;">'.$badge.'</td>'
                .'<td style="padding:6px 8px;color:#64748b;font-size:12px;">'.$notes.'</td>'
                .'</tr>';
        }

        $table = '<div style="max-height:52vh;overflow:auto;border:1px solid #e2e8f0;border-radius:8px;">'
            .'<table style="width:100%;border-collapse:collapse;font-size:13px;">'
            .'<thead><tr style="background:#f8fafc;text-align:left;">'
            .'<th style="padding:6px 8px;">Dòng</th><th style="padding:6px 8px;">Họ tên</th>'
            .'<th style="padding:6px 8px;">CCCD</th><th style="padding:6px 8px;">SĐT</th>'
            .'<th style="padding:6px 8px;">Trạng thái</th><th style="padding:6px 8px;">Ghi chú</th>'
            .'</tr></thead><tbody>'.$rows.'</tbody></table></div>';

        $hint = (int) $batch->valid_rows > 0
            ? '<p style="margin-top:12px;font-size:13px;color:#334155;">Bấm <b>Ghi các dòng hợp lệ</b> để tạo '.(int) $batch->valid_rows.' cư dân. Dòng lỗi sẽ bị bỏ qua.</p>'
            : '<p style="margin-top:12px;font-size:13px;color:#e11d48;">Không có dòng hợp lệ — hãy sửa file và tải lại.</p>';

        return new HtmlString($summary.$table.$hint);
    }

    private function previewStat(string $label, int $value, string $tone): string
    {
        $colors = ['slate' => '#475569', 'emerald' => '#059669', 'rose' => '#e11d48'];
        $c = $colors[$tone] ?? '#475569';

        return '<div style="flex:1;border:1px solid #e2e8f0;border-radius:8px;padding:8px 12px;">'
            .'<div style="color:#94a3b8;font-size:12px;">'.e($label).'</div>'
            .'<div style="font-size:20px;font-weight:700;color:'.$c.';">'.number_format($value).'</div></div>';
    }
}
