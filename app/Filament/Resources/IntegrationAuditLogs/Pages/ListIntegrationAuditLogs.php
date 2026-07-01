<?php

namespace App\Filament\Resources\IntegrationAuditLogs\Pages;

use App\Filament\Resources\IntegrationAuditLogs\IntegrationAuditLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIntegrationAuditLogs extends ListRecords
{
    protected static string $resource = IntegrationAuditLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
