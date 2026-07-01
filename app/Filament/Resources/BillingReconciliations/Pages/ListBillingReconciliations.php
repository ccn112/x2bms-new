<?php

namespace App\Filament\Resources\BillingReconciliations\Pages;

use App\Filament\Resources\BillingReconciliations\BillingReconciliationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBillingReconciliations extends ListRecords
{
    protected static string $resource = BillingReconciliationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
