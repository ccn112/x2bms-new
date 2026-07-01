<?php

namespace App\Filament\Resources\BillingReconciliations\Pages;

use App\Filament\Resources\BillingReconciliations\BillingReconciliationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBillingReconciliation extends EditRecord
{
    protected static string $resource = BillingReconciliationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
