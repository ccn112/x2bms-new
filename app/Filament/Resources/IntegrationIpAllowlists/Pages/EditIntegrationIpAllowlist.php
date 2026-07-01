<?php

namespace App\Filament\Resources\IntegrationIpAllowlists\Pages;

use App\Filament\Resources\IntegrationIpAllowlists\IntegrationIpAllowlistResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditIntegrationIpAllowlist extends EditRecord
{
    protected static string $resource = IntegrationIpAllowlistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
