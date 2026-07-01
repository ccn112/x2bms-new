<?php

namespace App\Filament\Resources\SupportEscalations\Pages;

use App\Filament\Resources\SupportEscalations\SupportEscalationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSupportEscalation extends EditRecord
{
    protected static string $resource = SupportEscalationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
