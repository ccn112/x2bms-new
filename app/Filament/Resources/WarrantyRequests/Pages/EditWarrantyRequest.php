<?php

namespace App\Filament\Resources\WarrantyRequests\Pages;

use App\Filament\Resources\WarrantyRequests\WarrantyRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWarrantyRequest extends EditRecord
{
    protected static string $resource = WarrantyRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
