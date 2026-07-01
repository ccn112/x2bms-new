<?php

namespace App\Filament\Resources\IotDevices\Pages;

use App\Filament\Resources\IotDevices\IotDeviceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditIotDevice extends EditRecord
{
    protected static string $resource = IotDeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
