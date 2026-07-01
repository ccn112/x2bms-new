<?php

namespace App\Filament\Resources\AccessDevices\Pages;

use App\Filament\Resources\AccessDevices\AccessDeviceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAccessDevice extends EditRecord
{
    protected static string $resource = AccessDeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
