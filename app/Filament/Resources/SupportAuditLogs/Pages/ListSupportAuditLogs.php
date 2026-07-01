<?php

namespace App\Filament\Resources\SupportAuditLogs\Pages;

use App\Filament\Resources\SupportAuditLogs\SupportAuditLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSupportAuditLogs extends ListRecords
{
    protected static string $resource = SupportAuditLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
