<?php

namespace App\Filament\Resources\GlobalUserAccounts\Pages;

use App\Filament\Resources\GlobalUserAccounts\GlobalUserAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGlobalUserAccounts extends ListRecords
{
    protected static string $resource = GlobalUserAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
