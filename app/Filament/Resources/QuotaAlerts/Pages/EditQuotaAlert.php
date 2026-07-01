<?php

namespace App\Filament\Resources\QuotaAlerts\Pages;

use App\Filament\Resources\QuotaAlerts\QuotaAlertResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditQuotaAlert extends EditRecord
{
    protected static string $resource = QuotaAlertResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
