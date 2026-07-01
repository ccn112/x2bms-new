<?php

namespace App\Filament\Resources\IntegrationCategories\Pages;

use App\Filament\Resources\IntegrationCategories\IntegrationCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIntegrationCategories extends ListRecords
{
    protected static string $resource = IntegrationCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
