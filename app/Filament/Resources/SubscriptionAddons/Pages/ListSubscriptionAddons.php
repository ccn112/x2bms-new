<?php

namespace App\Filament\Resources\SubscriptionAddons\Pages;

use App\Filament\Resources\SubscriptionAddons\SubscriptionAddonResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSubscriptionAddons extends ListRecords
{
    protected static string $resource = SubscriptionAddonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
