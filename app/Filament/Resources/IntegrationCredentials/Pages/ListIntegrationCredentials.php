<?php

namespace App\Filament\Resources\IntegrationCredentials\Pages;

use App\Filament\Resources\IntegrationCredentials\IntegrationCredentialResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIntegrationCredentials extends ListRecords
{
    protected static string $resource = IntegrationCredentialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
