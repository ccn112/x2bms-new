<?php

namespace App\Filament\Resources\IntegrationConnectionChecks\Pages;

use App\Filament\Resources\IntegrationConnectionChecks\IntegrationConnectionCheckResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIntegrationConnectionChecks extends ListRecords
{
    protected static string $resource = IntegrationConnectionCheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
