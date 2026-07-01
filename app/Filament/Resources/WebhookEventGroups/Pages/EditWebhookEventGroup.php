<?php

namespace App\Filament\Resources\WebhookEventGroups\Pages;

use App\Filament\Resources\WebhookEventGroups\WebhookEventGroupResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWebhookEventGroup extends EditRecord
{
    protected static string $resource = WebhookEventGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
