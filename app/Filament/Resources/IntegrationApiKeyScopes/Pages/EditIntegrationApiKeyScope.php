<?php

namespace App\Filament\Resources\IntegrationApiKeyScopes\Pages;

use App\Filament\Resources\IntegrationApiKeyScopes\IntegrationApiKeyScopeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditIntegrationApiKeyScope extends EditRecord
{
    protected static string $resource = IntegrationApiKeyScopeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
