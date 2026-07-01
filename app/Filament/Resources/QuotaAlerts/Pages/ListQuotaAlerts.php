<?php

namespace App\Filament\Resources\QuotaAlerts\Pages;

use App\Filament\Resources\QuotaAlerts\QuotaAlertResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListQuotaAlerts extends ListRecords
{
    protected static string $resource = QuotaAlertResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
