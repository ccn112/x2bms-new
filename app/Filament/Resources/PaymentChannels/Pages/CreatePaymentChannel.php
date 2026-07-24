<?php

namespace App\Filament\Resources\PaymentChannels\Pages;

use App\Filament\Resources\PaymentChannels\PaymentChannelResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentChannel extends CreateRecord
{
    protected static string $resource = PaymentChannelResource::class;
}
