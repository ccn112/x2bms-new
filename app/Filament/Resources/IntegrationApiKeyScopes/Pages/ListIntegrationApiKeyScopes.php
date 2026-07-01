<?php

namespace App\Filament\Resources\IntegrationApiKeyScopes\Pages;

use App\Filament\Resources\IntegrationApiKeyScopes\IntegrationApiKeyScopeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIntegrationApiKeyScopes extends ListRecords
{
    protected static string $resource = IntegrationApiKeyScopeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
