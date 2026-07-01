<?php

namespace App\Filament\Resources\TenantPartnerAssignments\Pages;

use App\Filament\Resources\TenantPartnerAssignments\TenantPartnerAssignmentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTenantPartnerAssignment extends EditRecord
{
    protected static string $resource = TenantPartnerAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
