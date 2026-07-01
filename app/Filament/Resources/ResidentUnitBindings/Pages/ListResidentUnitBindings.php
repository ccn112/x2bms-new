<?php

namespace App\Filament\Resources\ResidentUnitBindings\Pages;

use App\Filament\Resources\ResidentUnitBindings\ResidentUnitBindingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListResidentUnitBindings extends ListRecords
{
    protected static string $resource = ResidentUnitBindingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
