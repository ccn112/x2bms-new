<?php

namespace App\Filament\Sa\Resources\DocSpaces\Pages;

use App\Filament\Sa\Resources\DocSpaces\DocSpaceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDocSpaces extends ListRecords
{
    protected static string $resource = DocSpaceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
