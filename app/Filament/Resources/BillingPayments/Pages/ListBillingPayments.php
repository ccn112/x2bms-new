<?php

namespace App\Filament\Resources\BillingPayments\Pages;

use App\Filament\Resources\BillingPayments\BillingPaymentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBillingPayments extends ListRecords
{
    protected static string $resource = BillingPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
