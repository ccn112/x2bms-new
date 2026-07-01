<?php

namespace App\Filament\Resources\BillingInvoices\Pages;

use App\Filament\Resources\BillingInvoices\BillingInvoiceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBillingInvoice extends CreateRecord
{
    protected static string $resource = BillingInvoiceResource::class;
}
