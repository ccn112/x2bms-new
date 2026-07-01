<?php

namespace App\Filament\Resources\SmartDevices\Pages;

use App\Filament\Resources\SmartDevices\SmartDeviceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSmartDevice extends EditRecord
{
    protected static string $resource = SmartDeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
