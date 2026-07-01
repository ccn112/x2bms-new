<?php

namespace App\Filament\Resources\SubscriptionContracts\Pages;

use App\Filament\Resources\SubscriptionContracts\SubscriptionContractResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSubscriptionContract extends EditRecord
{
    protected static string $resource = SubscriptionContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
