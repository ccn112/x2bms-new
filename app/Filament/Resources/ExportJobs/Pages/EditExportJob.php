<?php

namespace App\Filament\Resources\ExportJobs\Pages;

use App\Filament\Resources\ExportJobs\ExportJobResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditExportJob extends EditRecord
{
    protected static string $resource = ExportJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
