<?php

namespace App\Filament\Resources\TenantSupportProfiles\Pages;

use App\Filament\Resources\TenantSupportProfiles\TenantSupportProfileResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTenantSupportProfiles extends ListRecords
{
    protected static string $resource = TenantSupportProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
