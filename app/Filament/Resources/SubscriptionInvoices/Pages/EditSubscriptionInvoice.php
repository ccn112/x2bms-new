<?php

namespace App\Filament\Resources\SubscriptionInvoices\Pages;

use App\Filament\Resources\SubscriptionInvoices\SubscriptionInvoiceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSubscriptionInvoice extends EditRecord
{
    protected static string $resource = SubscriptionInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
