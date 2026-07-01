<?php

namespace App\Filament\Resources\WebhookDeliveryAttempts\Pages;

use App\Filament\Resources\WebhookDeliveryAttempts\WebhookDeliveryAttemptResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWebhookDeliveryAttempt extends EditRecord
{
    protected static string $resource = WebhookDeliveryAttemptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
