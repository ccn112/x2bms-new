<?php

namespace App\Filament\Resources\HandoverBatches\Pages;

use App\Filament\Resources\HandoverBatches\HandoverBatchResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHandoverBatches extends ListRecords
{
    protected static string $resource = HandoverBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
