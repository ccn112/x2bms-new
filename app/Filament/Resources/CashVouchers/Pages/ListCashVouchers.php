<?php

namespace App\Filament\Resources\CashVouchers\Pages;

use App\Filament\Resources\CashVouchers\CashVoucherResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCashVouchers extends ListRecords
{
    protected static string $resource = CashVoucherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
