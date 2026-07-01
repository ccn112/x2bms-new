<?php

namespace App\Filament\Resources\IntegrationSecurityPolicies\Pages;

use App\Filament\Resources\IntegrationSecurityPolicies\IntegrationSecurityPolicyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIntegrationSecurityPolicies extends ListRecords
{
    protected static string $resource = IntegrationSecurityPolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
