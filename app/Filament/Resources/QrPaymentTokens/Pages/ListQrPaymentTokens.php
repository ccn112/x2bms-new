<?php

namespace App\Filament\Resources\QrPaymentTokens\Pages;

use App\Filament\Resources\QrPaymentTokens\QrPaymentTokenResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListQrPaymentTokens extends ListRecords
{
    protected static string $resource = QrPaymentTokenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
