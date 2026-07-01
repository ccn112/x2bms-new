<?php

namespace App\Filament\Resources\SupportKbCategories\Pages;

use App\Filament\Resources\SupportKbCategories\SupportKbCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSupportKbCategories extends ListRecords
{
    protected static string $resource = SupportKbCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
