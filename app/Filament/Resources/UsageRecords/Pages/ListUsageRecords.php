<?php

namespace App\Filament\Resources\UsageRecords\Pages;

use App\Filament\Resources\UsageRecords\UsageRecordResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUsageRecords extends ListRecords
{
    protected static string $resource = UsageRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
