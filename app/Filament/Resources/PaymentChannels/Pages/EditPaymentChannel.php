<?php

namespace App\Filament\Resources\PaymentChannels\Pages;

use App\Filament\Resources\PaymentChannels\PaymentChannelResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPaymentChannel extends EditRecord
{
    protected static string $resource = PaymentChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
