<?php

namespace App\Filament\Resources\BillingPeriods\Pages;

use App\Filament\Resources\BillingPeriods\BillingPeriodResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBillingPeriod extends EditRecord
{
    protected static string $resource = BillingPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
