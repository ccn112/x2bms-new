<?php

namespace App\Filament\Resources\SecurityIncidents\Pages;

use App\Filament\Resources\SecurityIncidents\SecurityIncidentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSecurityIncident extends EditRecord
{
    protected static string $resource = SecurityIncidentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
