<?php

namespace App\Filament\Resources\AccessDevices\Pages;

use App\Filament\Resources\AccessDevices\AccessDeviceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAccessDevices extends ListRecords
{
    protected static string $resource = AccessDeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
