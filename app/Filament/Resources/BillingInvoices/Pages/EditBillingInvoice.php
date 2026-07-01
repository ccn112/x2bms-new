<?php

namespace App\Filament\Resources\BillingInvoices\Pages;

use App\Filament\Resources\BillingInvoices\BillingInvoiceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBillingInvoice extends EditRecord
{
    protected static string $resource = BillingInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
