<?php

namespace App\Filament\Resources\PassThroughWallets\Pages;

use App\Filament\Resources\PassThroughWallets\PassThroughWalletResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPassThroughWallet extends EditRecord
{
    protected static string $resource = PassThroughWalletResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
