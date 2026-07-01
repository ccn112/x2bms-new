<?php

namespace App\Filament\Resources\IntegrationRateLimits\Pages;

use App\Filament\Resources\IntegrationRateLimits\IntegrationRateLimitResource;
use Filament\Resources\Pages\CreateRecord;

class CreateIntegrationRateLimit extends CreateRecord
{
    protected static string $resource = IntegrationRateLimitResource::class;
}
