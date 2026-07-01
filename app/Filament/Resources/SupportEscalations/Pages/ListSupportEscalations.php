<?php

namespace App\Filament\Resources\SupportEscalations\Pages;

use App\Filament\Resources\SupportEscalations\SupportEscalationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSupportEscalations extends ListRecords
{
    protected static string $resource = SupportEscalationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
