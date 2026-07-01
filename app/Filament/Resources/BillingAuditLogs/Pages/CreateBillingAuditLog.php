<?php

namespace App\Filament\Resources\BillingAuditLogs\Pages;

use App\Filament\Resources\BillingAuditLogs\BillingAuditLogResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBillingAuditLog extends CreateRecord
{
    protected static string $resource = BillingAuditLogResource::class;
}
