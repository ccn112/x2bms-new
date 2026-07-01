<?php

namespace App\Filament\Resources\DocumentTemplateCategories\Pages;

use App\Filament\Resources\DocumentTemplateCategories\DocumentTemplateCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDocumentTemplateCategory extends EditRecord
{
    protected static string $resource = DocumentTemplateCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
