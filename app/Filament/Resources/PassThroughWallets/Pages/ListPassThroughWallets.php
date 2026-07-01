<?php

namespace App\Filament\Resources\PassThroughWallets\Pages;

use App\Filament\Resources\PassThroughWallets\PassThroughWalletResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPassThroughWallets extends ListRecords
{
    protected static string $resource = PassThroughWalletResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
