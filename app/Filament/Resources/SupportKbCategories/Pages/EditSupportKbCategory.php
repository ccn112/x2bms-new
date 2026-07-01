<?php

namespace App\Filament\Resources\SupportKbCategories\Pages;

use App\Filament\Resources\SupportKbCategories\SupportKbCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSupportKbCategory extends EditRecord
{
    protected static string $resource = SupportKbCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
