<?php

namespace App\Filament\Resources\TenantProjectLinks\Pages;

use App\Filament\Resources\TenantProjectLinks\TenantProjectLinkResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTenantProjectLink extends EditRecord
{
    protected static string $resource = TenantProjectLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
