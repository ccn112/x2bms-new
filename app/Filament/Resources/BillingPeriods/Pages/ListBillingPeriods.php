<?php

namespace App\Filament\Resources\BillingPeriods\Pages;

use App\Filament\Resources\BillingPeriods\BillingPeriodResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBillingPeriods extends ListRecords
{
    protected static string $resource = BillingPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
