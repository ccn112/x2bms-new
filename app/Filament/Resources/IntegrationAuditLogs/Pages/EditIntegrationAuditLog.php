<?php

namespace App\Filament\Resources\IntegrationAuditLogs\Pages;

use App\Filament\Resources\IntegrationAuditLogs\IntegrationAuditLogResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditIntegrationAuditLog extends EditRecord
{
    protected static string $resource = IntegrationAuditLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
