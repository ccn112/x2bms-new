<?php

namespace App\Filament\Resources\ResidentBindingRequests\Pages;

use App\Filament\Resources\ResidentBindingRequests\ResidentBindingRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListResidentBindingRequests extends ListRecords
{
    protected static string $resource = ResidentBindingRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
