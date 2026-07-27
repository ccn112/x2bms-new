<?php

namespace App\Filament\Sa\Resources\DocPages\Pages;

use App\Filament\Sa\Resources\DocPages\DocPageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDocPages extends ListRecords
{
    protected static string $resource = DocPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
