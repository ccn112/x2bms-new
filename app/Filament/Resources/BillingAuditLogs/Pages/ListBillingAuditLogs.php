<?php

namespace App\Filament\Resources\BillingAuditLogs\Pages;

use App\Filament\Resources\BillingAuditLogs\BillingAuditLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBillingAuditLogs extends ListRecords
{
    protected static string $resource = BillingAuditLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
