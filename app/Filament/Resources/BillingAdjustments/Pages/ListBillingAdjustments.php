<?php

namespace App\Filament\Resources\BillingAdjustments\Pages;

use App\Filament\Resources\BillingAdjustments\BillingAdjustmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBillingAdjustments extends ListRecords
{
    protected static string $resource = BillingAdjustmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
