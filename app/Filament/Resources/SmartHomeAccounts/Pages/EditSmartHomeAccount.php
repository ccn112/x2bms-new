<?php

namespace App\Filament\Resources\SmartHomeAccounts\Pages;

use App\Filament\Resources\SmartHomeAccounts\SmartHomeAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSmartHomeAccount extends EditRecord
{
    protected static string $resource = SmartHomeAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
