<?php

namespace App\Filament\Resources\IntegrationAuditLogs\Pages;

use App\Filament\Resources\IntegrationAuditLogs\IntegrationAuditLogResource;
use Filament\Resources\Pages\CreateRecord;

class CreateIntegrationAuditLog extends CreateRecord
{
    protected static string $resource = IntegrationAuditLogResource::class;
}
