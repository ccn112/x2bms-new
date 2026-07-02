<?php

namespace App\Filament\Hq\Pages;

use App\Filament\Concerns\HqScreen;
use App\Models\ReportExportJob;
use App\Models\ReportSchedule;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;

/** HQ-05-09 — Xuất báo cáo & lịch gửi. */
class ReportExports extends Page
{
    use HqScreen;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-arrow-down';

    protected static string|\UnitEnum|null $navigationGroup = 'Báo cáo';

    protected static ?string $navigationLabel = 'Xuất báo cáo & lịch gửi';

    protected static ?int $navigationSort = 9;

    protected static ?string $title = 'Xuất báo cáo & lịch gửi';

    protected static ?string $slug = 'finance/reports';

    protected string $view = 'filament.hq.pages.report-exports';

    protected function getViewData(): array
    {
        $tid = app(CurrentContext::class)->tenantId();

        return [
            'schedules' => ReportSchedule::where('tenant_id', $tid)->latest('id')->get()->map(fn ($s) => [
                'name' => $s->name, 'type' => $s->report_type, 'freq' => $s->frequency, 'format' => $s->format,
                'recipients' => count($s->recipients ?? []), 'next' => optional($s->next_run_at)->format('d/m/Y'),
                'last' => optional($s->last_run_at)->format('d/m/Y'), 'status' => $s->status,
            ]),
            'jobs' => ReportExportJob::where('tenant_id', $tid)->latest('id')->get()->map(fn ($j) => [
                'type' => $j->report_type, 'format' => $j->format, 'status' => $j->status,
                'completed' => optional($j->completed_at)->format('d/m/Y H:i'), 'file' => $j->file_path,
            ]),
        ];
    }
}
