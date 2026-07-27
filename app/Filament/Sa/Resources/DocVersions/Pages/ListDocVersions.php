<?php

namespace App\Filament\Sa\Resources\DocVersions\Pages;

use App\Filament\Sa\Resources\DocVersions\DocVersionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDocVersions extends ListRecords
{
    protected static string $resource = DocVersionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
