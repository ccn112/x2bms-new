<?php

namespace App\Filament\Resources\EmergencyAlerts\Pages;

use App\Filament\Resources\EmergencyAlerts\EmergencyAlertResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEmergencyAlerts extends ListRecords
{
    protected static string $resource = EmergencyAlertResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
