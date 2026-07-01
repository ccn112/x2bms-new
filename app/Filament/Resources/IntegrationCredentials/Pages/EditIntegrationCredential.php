<?php

namespace App\Filament\Resources\IntegrationCredentials\Pages;

use App\Filament\Resources\IntegrationCredentials\IntegrationCredentialResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditIntegrationCredential extends EditRecord
{
    protected static string $resource = IntegrationCredentialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
