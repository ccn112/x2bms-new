<?php

namespace App\Filament\Resources\WebhookDeliveryAttempts\Pages;

use App\Filament\Resources\WebhookDeliveryAttempts\WebhookDeliveryAttemptResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWebhookDeliveryAttempts extends ListRecords
{
    protected static string $resource = WebhookDeliveryAttemptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
