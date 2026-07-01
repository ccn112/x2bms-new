<?php

namespace App\Filament\Resources\IntegrationConnectionChecks\Pages;

use App\Filament\Resources\IntegrationConnectionChecks\IntegrationConnectionCheckResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditIntegrationConnectionCheck extends EditRecord
{
    protected static string $resource = IntegrationConnectionCheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
