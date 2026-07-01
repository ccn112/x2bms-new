<?php

namespace App\Filament\Resources\SupportTeams\Pages;

use App\Filament\Resources\SupportTeams\SupportTeamResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSupportTeams extends ListRecords
{
    protected static string $resource = SupportTeamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
