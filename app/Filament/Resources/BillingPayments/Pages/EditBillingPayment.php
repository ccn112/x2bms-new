<?php

namespace App\Filament\Resources\BillingPayments\Pages;

use App\Filament\Resources\BillingPayments\BillingPaymentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBillingPayment extends EditRecord
{
    protected static string $resource = BillingPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
