<?php

namespace App\Filament\Sa\Resources\DocSpaces\Pages;

use App\Filament\Sa\Resources\DocSpaces\DocSpaceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDocSpace extends EditRecord
{
    protected static string $resource = DocSpaceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
