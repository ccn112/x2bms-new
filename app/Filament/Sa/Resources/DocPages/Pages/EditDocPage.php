<?php

namespace App\Filament\Sa\Resources\DocPages\Pages;

use App\Filament\Sa\Resources\DocPages\DocPageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDocPage extends EditRecord
{
    protected static string $resource = DocPageResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
