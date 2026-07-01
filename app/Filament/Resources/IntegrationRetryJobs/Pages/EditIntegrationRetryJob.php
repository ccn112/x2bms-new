<?php

namespace App\Filament\Resources\IntegrationRetryJobs\Pages;

use App\Filament\Resources\IntegrationRetryJobs\IntegrationRetryJobResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditIntegrationRetryJob extends EditRecord
{
    protected static string $resource = IntegrationRetryJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
