<?php

namespace App\Filament\Resources\SecurityIncidents\Pages;

use App\Filament\Resources\SecurityIncidents\SecurityIncidentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSecurityIncident extends CreateRecord
{
    protected static string $resource = SecurityIncidentResource::class;
}
