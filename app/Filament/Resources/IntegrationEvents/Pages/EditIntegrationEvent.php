<?php

namespace App\Filament\Resources\IntegrationEvents\Pages;

use App\Filament\Resources\IntegrationEvents\IntegrationEventResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditIntegrationEvent extends EditRecord
{
    protected static string $resource = IntegrationEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
