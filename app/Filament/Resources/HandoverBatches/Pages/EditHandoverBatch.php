<?php

namespace App\Filament\Resources\HandoverBatches\Pages;

use App\Filament\Resources\HandoverBatches\HandoverBatchResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHandoverBatch extends EditRecord
{
    protected static string $resource = HandoverBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
