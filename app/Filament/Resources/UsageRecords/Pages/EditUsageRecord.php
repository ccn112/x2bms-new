<?php

namespace App\Filament\Resources\UsageRecords\Pages;

use App\Filament\Resources\UsageRecords\UsageRecordResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUsageRecord extends EditRecord
{
    protected static string $resource = UsageRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
