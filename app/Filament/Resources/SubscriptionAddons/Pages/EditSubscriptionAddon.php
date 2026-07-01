<?php

namespace App\Filament\Resources\SubscriptionAddons\Pages;

use App\Filament\Resources\SubscriptionAddons\SubscriptionAddonResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSubscriptionAddon extends EditRecord
{
    protected static string $resource = SubscriptionAddonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
