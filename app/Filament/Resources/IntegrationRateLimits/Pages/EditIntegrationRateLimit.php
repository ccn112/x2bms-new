<?php

namespace App\Filament\Resources\IntegrationRateLimits\Pages;

use App\Filament\Resources\IntegrationRateLimits\IntegrationRateLimitResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditIntegrationRateLimit extends EditRecord
{
    protected static string $resource = IntegrationRateLimitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
