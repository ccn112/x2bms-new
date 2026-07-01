<?php

namespace App\Filament\Resources\SmartHomeAccounts\Pages;

use App\Filament\Resources\SmartHomeAccounts\SmartHomeAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSmartHomeAccounts extends ListRecords
{
    protected static string $resource = SmartHomeAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
