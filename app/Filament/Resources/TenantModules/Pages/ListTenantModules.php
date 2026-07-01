<?php

namespace App\Filament\Resources\TenantModules\Pages;

use App\Filament\Resources\TenantModules\TenantModuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTenantModules extends ListRecords
{
    protected static string $resource = TenantModuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
