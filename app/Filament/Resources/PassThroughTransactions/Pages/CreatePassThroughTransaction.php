<?php

namespace App\Filament\Resources\PassThroughTransactions\Pages;

use App\Filament\Resources\PassThroughTransactions\PassThroughTransactionResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePassThroughTransaction extends CreateRecord
{
    protected static string $resource = PassThroughTransactionResource::class;
}
