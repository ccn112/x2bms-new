<?php

namespace App\Filament\Resources\PassThroughTransactions\Pages;

use App\Filament\Resources\PassThroughTransactions\PassThroughTransactionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPassThroughTransaction extends EditRecord
{
    protected static string $resource = PassThroughTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
