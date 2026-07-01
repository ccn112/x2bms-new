<?php

namespace App\Filament\Resources\IntegrationIncidents\Pages;

use App\Filament\Resources\IntegrationIncidents\IntegrationIncidentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateIntegrationIncident extends CreateRecord
{
    protected static string $resource = IntegrationIncidentResource::class;
}
