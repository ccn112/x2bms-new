<?php

namespace App\Filament\Resources\IntegrationRetryJobs\Pages;

use App\Filament\Resources\IntegrationRetryJobs\IntegrationRetryJobResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIntegrationRetryJobs extends ListRecords
{
    protected static string $resource = IntegrationRetryJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
