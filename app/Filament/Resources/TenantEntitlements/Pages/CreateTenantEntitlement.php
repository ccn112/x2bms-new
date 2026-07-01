<?php

namespace App\Filament\Resources\TenantEntitlements\Pages;

use App\Filament\Resources\TenantEntitlements\TenantEntitlementResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTenantEntitlement extends CreateRecord
{
    protected static string $resource = TenantEntitlementResource::class;
}
