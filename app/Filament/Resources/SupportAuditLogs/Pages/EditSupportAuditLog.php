<?php

namespace App\Filament\Resources\SupportAuditLogs\Pages;

use App\Filament\Resources\SupportAuditLogs\SupportAuditLogResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSupportAuditLog extends EditRecord
{
    protected static string $resource = SupportAuditLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
