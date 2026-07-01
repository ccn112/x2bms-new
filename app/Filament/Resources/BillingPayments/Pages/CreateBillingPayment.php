<?php

namespace App\Filament\Resources\BillingPayments\Pages;

use App\Filament\Resources\BillingPayments\BillingPaymentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBillingPayment extends CreateRecord
{
    protected static string $resource = BillingPaymentResource::class;
}
