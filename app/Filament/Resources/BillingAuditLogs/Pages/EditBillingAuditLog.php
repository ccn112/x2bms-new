<?php

namespace App\Filament\Resources\BillingAuditLogs\Pages;

use App\Filament\Resources\BillingAuditLogs\BillingAuditLogResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBillingAuditLog extends EditRecord
{
    protected static string $resource = BillingAuditLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
