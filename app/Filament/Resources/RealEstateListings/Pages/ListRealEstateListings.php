<?php

namespace App\Filament\Resources\RealEstateListings\Pages;

use App\Filament\Resources\RealEstateListings\RealEstateListingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRealEstateListings extends ListRecords
{
    protected static string $resource = RealEstateListingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
