<?php

namespace App\Filament\Resources\SharedPartnerCategories\Pages;

use App\Filament\Resources\SharedPartnerCategories\SharedPartnerCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSharedPartnerCategories extends ListRecords
{
    protected static string $resource = SharedPartnerCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
