<?php

namespace App\Filament\Resources\GlobalUserAccounts\Pages;

use App\Filament\Resources\GlobalUserAccounts\GlobalUserAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGlobalUserAccount extends EditRecord
{
    protected static string $resource = GlobalUserAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
