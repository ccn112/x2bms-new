<?php

namespace App\Filament\Resources\WebhookEventGroups\Pages;

use App\Filament\Resources\WebhookEventGroups\WebhookEventGroupResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWebhookEventGroup extends CreateRecord
{
    protected static string $resource = WebhookEventGroupResource::class;
}
