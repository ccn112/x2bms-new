<?php

namespace App\Filament\Resources\IntegrationApiKeys\Pages;

use App\Filament\Resources\IntegrationApiKeys\IntegrationApiKeyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIntegrationApiKeys extends ListRecords
{
    protected static string $resource = IntegrationApiKeyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
