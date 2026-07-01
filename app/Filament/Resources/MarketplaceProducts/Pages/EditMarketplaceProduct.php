<?php

namespace App\Filament\Resources\MarketplaceProducts\Pages;

use App\Filament\Resources\MarketplaceProducts\MarketplaceProductResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMarketplaceProduct extends EditRecord
{
    protected static string $resource = MarketplaceProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
