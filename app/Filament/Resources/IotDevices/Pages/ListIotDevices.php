<?php

namespace App\Filament\Resources\IotDevices\Pages;

use App\Filament\Resources\IotDevices\IotDeviceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIotDevices extends ListRecords
{
    protected static string $resource = IotDeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
