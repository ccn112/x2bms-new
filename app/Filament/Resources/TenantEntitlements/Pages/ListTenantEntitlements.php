<?php

namespace App\Filament\Resources\TenantEntitlements\Pages;

use App\Filament\Resources\TenantEntitlements\TenantEntitlementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTenantEntitlements extends ListRecords
{
    protected static string $resource = TenantEntitlementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
