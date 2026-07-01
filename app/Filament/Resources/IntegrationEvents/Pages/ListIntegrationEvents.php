<?php

namespace App\Filament\Resources\IntegrationEvents\Pages;

use App\Filament\Resources\IntegrationEvents\IntegrationEventResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIntegrationEvents extends ListRecords
{
    protected static string $resource = IntegrationEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
