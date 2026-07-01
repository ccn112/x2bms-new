<?php

namespace App\Filament\Resources\EmergencyAlerts\Pages;

use App\Filament\Resources\EmergencyAlerts\EmergencyAlertResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEmergencyAlert extends EditRecord
{
    protected static string $resource = EmergencyAlertResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
