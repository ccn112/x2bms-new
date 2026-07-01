<?php

namespace App\Filament\Resources\IntegrationApiKeyScopes\Pages;

use App\Filament\Resources\IntegrationApiKeyScopes\IntegrationApiKeyScopeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateIntegrationApiKeyScope extends CreateRecord
{
    protected static string $resource = IntegrationApiKeyScopeResource::class;
}
