<?php

namespace App\Filament\Resources\DataCorrectionRequests\Pages;

use App\Filament\Resources\DataCorrectionRequests\DataCorrectionRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDataCorrectionRequests extends ListRecords
{
    protected static string $resource = DataCorrectionRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
