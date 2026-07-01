<?php

namespace App\Filament\Resources\PublicProjects\Pages;

use App\Filament\Resources\PublicProjects\PublicProjectResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPublicProject extends EditRecord
{
    protected static string $resource = PublicProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
