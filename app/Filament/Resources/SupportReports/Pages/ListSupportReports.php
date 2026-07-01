<?php

namespace App\Filament\Resources\SupportReports\Pages;

use App\Filament\Resources\SupportReports\SupportReportResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSupportReports extends ListRecords
{
    protected static string $resource = SupportReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
