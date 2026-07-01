<?php

namespace App\Filament\Resources\IntegrationEvents\Pages;

use App\Filament\Resources\IntegrationEvents\IntegrationEventResource;
use Filament\Resources\Pages\CreateRecord;

class CreateIntegrationEvent extends CreateRecord
{
    protected static string $resource = IntegrationEventResource::class;
}
