<?php

namespace App\Filament\Resources\IntegrationRateLimits\Pages;

use App\Filament\Resources\IntegrationRateLimits\IntegrationRateLimitResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIntegrationRateLimits extends ListRecords
{
    protected static string $resource = IntegrationRateLimitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
