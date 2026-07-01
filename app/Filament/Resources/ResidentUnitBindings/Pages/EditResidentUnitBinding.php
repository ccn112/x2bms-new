<?php

namespace App\Filament\Resources\ResidentUnitBindings\Pages;

use App\Filament\Resources\ResidentUnitBindings\ResidentUnitBindingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditResidentUnitBinding extends EditRecord
{
    protected static string $resource = ResidentUnitBindingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
