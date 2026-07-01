<?php

namespace App\Filament\Resources\WebhookDeliveryAttempts\Pages;

use App\Filament\Resources\WebhookDeliveryAttempts\WebhookDeliveryAttemptResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWebhookDeliveryAttempt extends CreateRecord
{
    protected static string $resource = WebhookDeliveryAttemptResource::class;
}
