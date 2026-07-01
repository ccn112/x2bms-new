<?php

namespace App\Filament\Resources\IntegrationApiKeys\Pages;

use App\Filament\Resources\IntegrationApiKeys\IntegrationApiKeyResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditIntegrationApiKey extends EditRecord
{
    protected static string $resource = IntegrationApiKeyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
