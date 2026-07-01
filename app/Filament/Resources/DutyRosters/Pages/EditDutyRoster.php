<?php

namespace App\Filament\Resources\DutyRosters\Pages;

use App\Filament\Resources\DutyRosters\DutyRosterResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDutyRoster extends EditRecord
{
    protected static string $resource = DutyRosterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
