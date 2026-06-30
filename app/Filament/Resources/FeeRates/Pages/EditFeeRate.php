<?php

namespace App\Filament\Resources\FeeRates\Pages;

use App\Filament\Resources\FeeRates\FeeRateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFeeRate extends EditRecord
{
    protected static string $resource = FeeRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
