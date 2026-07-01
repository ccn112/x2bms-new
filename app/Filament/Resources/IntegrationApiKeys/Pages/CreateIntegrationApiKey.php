<?php

namespace App\Filament\Resources\IntegrationApiKeys\Pages;

use App\Filament\Resources\IntegrationApiKeys\IntegrationApiKeyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateIntegrationApiKey extends CreateRecord
{
    protected static string $resource = IntegrationApiKeyResource::class;
}
