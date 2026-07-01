<?php

namespace App\Filament\Resources\IntegrationIpAllowlists\Pages;

use App\Filament\Resources\IntegrationIpAllowlists\IntegrationIpAllowlistResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIntegrationIpAllowlists extends ListRecords
{
    protected static string $resource = IntegrationIpAllowlistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
