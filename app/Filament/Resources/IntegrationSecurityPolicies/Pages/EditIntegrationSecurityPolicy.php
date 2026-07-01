<?php

namespace App\Filament\Resources\IntegrationSecurityPolicies\Pages;

use App\Filament\Resources\IntegrationSecurityPolicies\IntegrationSecurityPolicyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditIntegrationSecurityPolicy extends EditRecord
{
    protected static string $resource = IntegrationSecurityPolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
