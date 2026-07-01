<?php

namespace App\Filament\Resources\UsagePeriods\Pages;

use App\Filament\Resources\UsagePeriods\UsagePeriodResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUsagePeriods extends ListRecords
{
    protected static string $resource = UsagePeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
