<?php

namespace App\Filament\Resources\TenantProjectLinks\Pages;

use App\Filament\Resources\TenantProjectLinks\TenantProjectLinkResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTenantProjectLink extends CreateRecord
{
    protected static string $resource = TenantProjectLinkResource::class;
}
