<?php

namespace App\Filament\Resources\ExportJobs\Pages;

use App\Filament\Resources\ExportJobs\ExportJobResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExportJobs extends ListRecords
{
    protected static string $resource = ExportJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
