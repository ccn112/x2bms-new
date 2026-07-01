<?php

namespace App\Filament\Resources\TenantPartnerAssignments\Pages;

use App\Filament\Resources\TenantPartnerAssignments\TenantPartnerAssignmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTenantPartnerAssignments extends ListRecords
{
    protected static string $resource = TenantPartnerAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
