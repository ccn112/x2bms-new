<?php

namespace App\Filament\Resources\DutyRosters\Pages;

use App\Filament\Resources\DutyRosters\DutyRosterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDutyRosters extends ListRecords
{
    protected static string $resource = DutyRosterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
