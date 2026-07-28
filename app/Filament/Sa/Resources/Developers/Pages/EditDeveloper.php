<?php

namespace App\Filament\Sa\Resources\Developers\Pages;

use App\Filament\Sa\Resources\Developers\DeveloperResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDeveloper extends EditRecord
{
    protected static string $resource = DeveloperResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
