<?php

namespace App\Filament\Hq\Pages;

use App\Filament\Concerns\HqScreen;
use App\Models\ImportBatch;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;

/**
 * HQ-01-10 — Import dự án & nhân sự. Wizard (upload → map → validate → confirm → complete) +
 * bảng preview với badge kiểm tra + panel thông tin file/tổng dòng/hợp lệ/lỗi.
 */
class ProjectEmployeeImport extends Page
{
    use HqScreen;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static string|\UnitEnum|null $navigationGroup = 'Quản lý dự án';

    protected static ?string $navigationLabel = 'Import dự án & nhân sự';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Import dự án & nhân sự';

    protected static ?string $slug = 'imports/projects-employees';

    protected string $view = 'filament.hq.pages.project-employee-import';

    protected function getViewData(): array
    {
        $tid = app(CurrentContext::class)->tenantId();
        $batch = ImportBatch::where('tenant_id', $tid)->with('rows')->latest('id')->first();

        return [
            'batch' => $batch,
            'rows' => $batch?->rows->sortBy('row_number')->values() ?? collect(),
        ];
    }
}
