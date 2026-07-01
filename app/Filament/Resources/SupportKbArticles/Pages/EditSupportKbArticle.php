<?php

namespace App\Filament\Resources\SupportKbArticles\Pages;

use App\Filament\Resources\SupportKbArticles\SupportKbArticleResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditSupportKbArticle extends EditRecord
{
    protected static string $resource = SupportKbArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
