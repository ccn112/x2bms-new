<?php

namespace App\Filament\Resources\IntegrationIncidents\Pages;

use App\Filament\Resources\IntegrationIncidents\IntegrationIncidentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIntegrationIncidents extends ListRecords
{
    protected static string $resource = IntegrationIncidentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
