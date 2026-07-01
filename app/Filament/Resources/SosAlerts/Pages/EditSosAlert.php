<?php

namespace App\Filament\Resources\SosAlerts\Pages;

use App\Filament\Resources\SosAlerts\SosAlertResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSosAlert extends EditRecord
{
    protected static string $resource = SosAlertResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
