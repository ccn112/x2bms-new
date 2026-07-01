<?php

namespace App\Filament\Resources\SupportKbArticles\Pages;

use App\Filament\Resources\SupportKbArticles\SupportKbArticleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSupportKbArticles extends ListRecords
{
    protected static string $resource = SupportKbArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
