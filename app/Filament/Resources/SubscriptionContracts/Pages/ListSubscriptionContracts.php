<?php

namespace App\Filament\Resources\SubscriptionContracts\Pages;

use App\Filament\Resources\SubscriptionContracts\SubscriptionContractResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSubscriptionContracts extends ListRecords
{
    protected static string $resource = SubscriptionContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
