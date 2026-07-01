<?php

namespace App\Filament\Resources\UsagePeriods\Pages;

use App\Filament\Resources\UsagePeriods\UsagePeriodResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUsagePeriod extends EditRecord
{
    protected static string $resource = UsagePeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
