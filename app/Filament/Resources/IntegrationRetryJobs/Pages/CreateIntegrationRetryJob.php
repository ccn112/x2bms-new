<?php

namespace App\Filament\Resources\IntegrationRetryJobs\Pages;

use App\Filament\Resources\IntegrationRetryJobs\IntegrationRetryJobResource;
use Filament\Resources\Pages\CreateRecord;

class CreateIntegrationRetryJob extends CreateRecord
{
    protected static string $resource = IntegrationRetryJobResource::class;
}
