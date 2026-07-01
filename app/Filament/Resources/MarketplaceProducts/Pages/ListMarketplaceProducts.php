<?php

namespace App\Filament\Resources\MarketplaceProducts\Pages;

use App\Filament\Resources\MarketplaceProducts\MarketplaceProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMarketplaceProducts extends ListRecords
{
    protected static string $resource = MarketplaceProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
