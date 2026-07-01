<?php

namespace App\Filament\Resources\TenantModules\Pages;

use App\Filament\Resources\TenantModules\TenantModuleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTenantModule extends EditRecord
{
    protected static string $resource = TenantModuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
