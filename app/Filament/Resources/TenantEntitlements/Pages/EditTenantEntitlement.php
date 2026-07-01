<?php

namespace App\Filament\Resources\TenantEntitlements\Pages;

use App\Filament\Resources\TenantEntitlements\TenantEntitlementResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTenantEntitlement extends EditRecord
{
    protected static string $resource = TenantEntitlementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
