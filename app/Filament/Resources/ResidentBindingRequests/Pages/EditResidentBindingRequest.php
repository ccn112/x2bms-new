<?php

namespace App\Filament\Resources\ResidentBindingRequests\Pages;

use App\Filament\Resources\ResidentBindingRequests\ResidentBindingRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditResidentBindingRequest extends EditRecord
{
    protected static string $resource = ResidentBindingRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
