<?php

namespace App\Filament\Resources\TenantModules\Pages;

use App\Filament\Resources\TenantModules\TenantModuleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTenantModule extends CreateRecord
{
    protected static string $resource = TenantModuleResource::class;
}
