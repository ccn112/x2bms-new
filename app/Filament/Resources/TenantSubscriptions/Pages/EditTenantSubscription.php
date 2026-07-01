<?php

namespace App\Filament\Resources\TenantSubscriptions\Pages;

use App\Filament\Resources\TenantSubscriptions\TenantSubscriptionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTenantSubscription extends EditRecord
{
    protected static string $resource = TenantSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
