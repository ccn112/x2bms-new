<?php

namespace App\Filament\Resources\RealEstateListings\Pages;

use App\Filament\Resources\RealEstateListings\RealEstateListingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRealEstateListing extends EditRecord
{
    protected static string $resource = RealEstateListingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
