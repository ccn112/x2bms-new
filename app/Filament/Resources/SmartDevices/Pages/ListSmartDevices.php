<?php

namespace App\Filament\Resources\SmartDevices\Pages;

use App\Filament\Resources\SmartDevices\SmartDeviceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSmartDevices extends ListRecords
{
    protected static string $resource = SmartDeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
