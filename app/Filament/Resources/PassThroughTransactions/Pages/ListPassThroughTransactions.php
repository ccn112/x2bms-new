<?php

namespace App\Filament\Resources\PassThroughTransactions\Pages;

use App\Filament\Resources\PassThroughTransactions\PassThroughTransactionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPassThroughTransactions extends ListRecords
{
    protected static string $resource = PassThroughTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
