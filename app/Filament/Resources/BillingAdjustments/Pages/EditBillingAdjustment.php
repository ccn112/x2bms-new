<?php

namespace App\Filament\Resources\BillingAdjustments\Pages;

use App\Filament\Resources\BillingAdjustments\BillingAdjustmentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBillingAdjustment extends EditRecord
{
    protected static string $resource = BillingAdjustmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
