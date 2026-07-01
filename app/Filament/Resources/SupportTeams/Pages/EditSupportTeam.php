<?php

namespace App\Filament\Resources\SupportTeams\Pages;

use App\Filament\Resources\SupportTeams\SupportTeamResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSupportTeam extends EditRecord
{
    protected static string $resource = SupportTeamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
