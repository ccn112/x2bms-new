<?php

namespace App\Filament\Resources\PaymentChannels\Pages;

use App\Filament\Resources\PaymentChannels\PaymentChannelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPaymentChannels extends ListRecords
{
    protected static string $resource = PaymentChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
