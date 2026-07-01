<?php

namespace App\Filament\Resources\IntegrationIncidents\Pages;

use App\Filament\Resources\IntegrationIncidents\IntegrationIncidentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditIntegrationIncident extends EditRecord
{
    protected static string $resource = IntegrationIncidentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
