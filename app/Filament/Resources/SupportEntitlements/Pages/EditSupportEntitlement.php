<?php

namespace App\Filament\Resources\SupportEntitlements\Pages;

use App\Filament\Resources\SupportEntitlements\SupportEntitlementResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSupportEntitlement extends EditRecord
{
    protected static string $resource = SupportEntitlementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
