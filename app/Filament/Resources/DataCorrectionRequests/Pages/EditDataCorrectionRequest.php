<?php

namespace App\Filament\Resources\DataCorrectionRequests\Pages;

use App\Filament\Resources\DataCorrectionRequests\DataCorrectionRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditDataCorrectionRequest extends EditRecord
{
    protected static string $resource = DataCorrectionRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
