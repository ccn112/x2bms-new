<?php

namespace App\Filament\Resources\SharedPartnerCategories\Pages;

use App\Filament\Resources\SharedPartnerCategories\SharedPartnerCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSharedPartnerCategory extends EditRecord
{
    protected static string $resource = SharedPartnerCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
