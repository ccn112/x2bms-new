<?php

namespace App\Filament\Sa\Resources\DocPages\Pages;

use App\Filament\Sa\Resources\DocPages\DocPageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDocPage extends CreateRecord
{
    protected static string $resource = DocPageResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['updated_by'] = auth()->id();

        return $data;
    }
}
