<?php

namespace App\Filament\Resources\SupportReports\Pages;

use App\Filament\Resources\SupportReports\SupportReportResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSupportReport extends EditRecord
{
    protected static string $resource = SupportReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
