<?php

namespace App\Filament\Resources\PlatformContents\Pages;

use App\Filament\Resources\PlatformContents\PlatformContentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPlatformContent extends EditRecord
{
    protected static string $resource = PlatformContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
