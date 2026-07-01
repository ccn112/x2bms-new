<?php

namespace App\Filament\Resources\LoyaltyAccounts\Pages;

use App\Filament\Resources\LoyaltyAccounts\LoyaltyAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLoyaltyAccount extends EditRecord
{
    protected static string $resource = LoyaltyAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
