<?php

namespace App\Filament\Resources\IntegrationConnectionChecks\Pages;

use App\Filament\Resources\IntegrationConnectionChecks\IntegrationConnectionCheckResource;
use Filament\Resources\Pages\CreateRecord;

class CreateIntegrationConnectionCheck extends CreateRecord
{
    protected static string $resource = IntegrationConnectionCheckResource::class;
}
