<?php

namespace App\Filament\Resources\QrPaymentTokens\Pages;

use App\Filament\Resources\QrPaymentTokens\QrPaymentTokenResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQrPaymentToken extends CreateRecord
{
    protected static string $resource = QrPaymentTokenResource::class;
}
