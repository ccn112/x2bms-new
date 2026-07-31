<?php

namespace App\Filament\Concerns;

use App\Jobs\CommitImportBatchJob;
use App\Models\ImportBatch;
use App\Support\Context\CurrentContext;
use App\Support\Import\Profiles\BillingChargeImportProfile;
use App\Support\Import\StagingImporter;
use App\Support\Storage\TenantStorage;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Nhập khoản phí từ Excel/CSV cho kế toán — theo đúng khuôn `ImportsResidentsFromExcel`,
 * trên `StagingImporter` + `BillingChargeImportProfile` dùng chung.
 * `docs/BILLING_IMPORT_SPEC_20260731.md`.
 */
trait ImportsBillingChargesFromExcel
{
    public function billingChargeImportAction(): Action
    {
        return Action::make('billingChargeImport')
            ->label('Nhập khoản phí')
            ->icon('heroicon-m-arrow-up-tray')
            ->color('gray')
            ->modalHeading('Nhập khoản phí từ Excel/CSV')
            ->modalDescription('Bắt buộc: Mã căn hộ, Kỳ phí, Mã loại phí, Thành tiền. Tiền là số nguyên đồng (không số lẻ). Bảng kê sinh ra luôn ở trạng thái "chờ duyệt" — cư dân chưa thấy cho tới khi trưởng ban phát hành.')
            ->modalIcon('heroicon-o-arrow-up-tray')
            ->modalWidth('lg')
            ->modalSubmitActionLabel('Kiểm tra dữ liệu')
            ->extraModalFooterActions([
                Action::make('downloadBillingTemplateInline')
                    ->label('Tải file mẫu (.xlsx)')
                    ->icon('heroicon-m-document-arrow-down')
                    ->link()
                    ->color('gray')
                    ->action(fn (): BinaryFileResponse => $this->downloadBillingChargeImportTemplate()),
            ])
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
                    ->disk(app(TenantStorage::class)->diskName())
                    ->directory(fn (): string => app(TenantStorage::class)->prefix().'/_incoming/billing_charges')
                    ->required(),
            ])
            ->action(function (array $data): void {
                $ctx = $this->billingChargeImportContext((int) $data['building_id']);
                $ts = app(TenantStorage::class);
                $uploadedKey = (string) $data['file'];

                $batch = app(StagingImporter::class)->stage(
                    $ts->localReadablePath($uploadedKey),
                    basename($uploadedKey),
                    new BillingChargeImportProfile,
                    $ctx,
                    $uploadedKey,
                );

                $finalKey = $ts->key('billing_charges/import/'.$batch->id.'/'.basename($uploadedKey), $ctx['tenant_id'], $ctx['building_id']);
                $ts->move($uploadedKey, $finalKey);
                $batch->update(['storage_path' => $finalKey]);

                $this->replaceMountedAction('billingChargeImportPreview', ['batch' => $batch->id]);
            });
    }

    /** Tải file mẫu .xlsx sinh từ ĐÚNG cột của BillingChargeImportProfile (luôn khớp). */
    public function downloadBillingChargeImportTemplate(): BinaryFileResponse
    {
        $cols = (new BillingChargeImportProfile)->columns();

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
        $path = $dir.'/mau_import_khoan_phi_'.now()->format('His').'.xlsx';

        SimpleExcelWriter::create($path)
            ->addRow($full)
            ->addRow($min)
            ->close();

        return response()->download($path, 'mau_import_khoan_phi.xlsx')->deleteFileAfterSend();
    }

    public function billingChargeImportPreviewAction(): Action
    {
        return Action::make('billingChargeImportPreview')
            ->modalHeading('Xem trước & xác nhận nhập khoản phí')
            ->modalIcon('heroicon-o-clipboard-document-check')
            ->modalWidth('4xl')
            ->modalSubmitActionLabel('Ghi các dòng hợp lệ')
            ->modalContent(fn (array $arguments): HtmlString => $this->billingImportPreviewContent($arguments))
            ->action(function (array $arguments): void {
                $batch = ImportBatch::findOrFail($arguments['batch']);

                if ((int) $batch->valid_rows === 0) {
                    Notification::make()->title('Không có dòng hợp lệ để ghi')->warning()->send();

                    return;
                }

                CommitImportBatchJob::dispatch($batch->id, $this->billingChargeImportContext((int) $batch->building_id));
                $batch->update(['status' => 'committing']);

                if (method_exists($this, 'audit')) {
                    $this->audit('billing_charge.import', "Đưa vào hàng đợi ghi {$batch->valid_rows} dòng khoản phí từ file {$batch->file_name}.");
                }

                Notification::make()
                    ->title('Đã đưa vào hàng đợi xử lý nền')
                    ->body("Đang ghi {$batch->valid_rows} dòng hợp lệ. Bảng kê sinh ra ở trạng thái \"chờ duyệt\". Theo dõi ở màn \"Nhật ký Import/Export\".")
                    ->success()->send();

                if (method_exists($this, 'refreshTable')) {
                    $this->refreshTable();
                }
            });
    }

    /** @return array{tenant_id:int, building_id:int, user_id:int|null} */
    protected function billingChargeImportContext(int $buildingId): array
    {
        $user = auth()->user();

        return [
            'tenant_id' => $user->tenant_id,
            'building_id' => $buildingId,
            'user_id' => $user->id,
        ];
    }

    private function billingImportPreviewContent(array $arguments): HtmlString
    {
        $batch = ImportBatch::with(['rows' => fn ($q) => $q->orderBy('row_number')])->findOrFail($arguments['batch']);

        $summary = '<div class="flex gap-3 mb-4 text-sm">'
            .$this->billingPreviewStat('Tổng dòng', (int) $batch->total_rows, 'slate')
            .$this->billingPreviewStat('Hợp lệ', (int) $batch->valid_rows, 'emerald')
            .$this->billingPreviewStat('Lỗi', (int) $batch->error_rows, 'rose')
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
            $amount = is_int($p['amount'] ?? null) ? number_format((int) $p['amount']).'đ' : (string) ($p['amount'] ?? '');

            $rows .= '<tr style="border-top:1px solid #e2e8f0;">'
                .'<td style="padding:6px 8px;color:#64748b;">'.e((string) $r->row_number).'</td>'
                .'<td style="padding:6px 8px;">'.e((string) ($p['apartment_code'] ?? '')).'</td>'
                .'<td style="padding:6px 8px;">'.e((string) ($p['fee_type_code'] ?? '')).'</td>'
                .'<td style="padding:6px 8px;">'.e((string) ($p['subject_ref'] ?? '')).'</td>'
                .'<td style="padding:6px 8px;text-align:right;">'.e($amount).'</td>'
                .'<td style="padding:6px 8px;white-space:nowrap;">'.$badge.'</td>'
                .'<td style="padding:6px 8px;color:#64748b;font-size:12px;">'.$notes.'</td>'
                .'</tr>';
        }

        $table = '<div style="max-height:52vh;overflow:auto;border:1px solid #e2e8f0;border-radius:8px;">'
            .'<table style="width:100%;border-collapse:collapse;font-size:13px;">'
            .'<thead><tr style="background:#f8fafc;text-align:left;">'
            .'<th style="padding:6px 8px;">Dòng</th><th style="padding:6px 8px;">Căn hộ</th>'
            .'<th style="padding:6px 8px;">Loại phí</th><th style="padding:6px 8px;">Tài sản</th>'
            .'<th style="padding:6px 8px;text-align:right;">Thành tiền</th>'
            .'<th style="padding:6px 8px;">Trạng thái</th><th style="padding:6px 8px;">Ghi chú</th>'
            .'</tr></thead><tbody>'.$rows.'</tbody></table></div>';

        $hint = (int) $batch->valid_rows > 0
            ? '<p style="margin-top:12px;font-size:13px;color:#334155;">Bấm <b>Ghi các dòng hợp lệ</b> để tạo/cập nhật '.(int) $batch->valid_rows.' dòng phí. Bảng kê liên quan ở trạng thái "chờ duyệt". Dòng lỗi sẽ bị bỏ qua.</p>'
            : '<p style="margin-top:12px;font-size:13px;color:#e11d48;">Không có dòng hợp lệ — hãy sửa file và tải lại.</p>';

        return new HtmlString($summary.$table.$hint);
    }

    private function billingPreviewStat(string $label, int $value, string $tone): string
    {
        $colors = ['slate' => '#475569', 'emerald' => '#059669', 'rose' => '#e11d48'];
        $c = $colors[$tone] ?? '#475569';

        return '<div style="flex:1;border:1px solid #e2e8f0;border-radius:8px;padding:8px 12px;">'
            .'<div style="color:#94a3b8;font-size:12px;">'.e($label).'</div>'
            .'<div style="font-size:20px;font-weight:700;color:'.$c.';">'.number_format($value).'</div></div>';
    }
}
