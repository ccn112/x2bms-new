<?php

namespace App\Filament\Resources\WebhookEventGroups\Pages;

use App\Filament\Resources\WebhookEventGroups\WebhookEventGroupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWebhookEventGroups extends ListRecords
{
    protected static string $resource = WebhookEventGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
