<?php

namespace App\Filament\Resources\TenantSupportProfiles\Pages;

use App\Filament\Resources\TenantSupportProfiles\TenantSupportProfileResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTenantSupportProfile extends EditRecord
{
    protected static string $resource = TenantSupportProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
