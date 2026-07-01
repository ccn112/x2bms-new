<?php

namespace App\Filament\Resources\IntercomEvents\Pages;

use App\Filament\Resources\IntercomEvents\IntercomEventResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIntercomEvents extends ListRecords
{
    protected static string $resource = IntercomEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
