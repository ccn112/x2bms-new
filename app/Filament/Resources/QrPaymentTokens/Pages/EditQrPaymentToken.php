<?php

namespace App\Filament\Resources\QrPaymentTokens\Pages;

use App\Filament\Resources\QrPaymentTokens\QrPaymentTokenResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditQrPaymentToken extends EditRecord
{
    protected static string $resource = QrPaymentTokenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
