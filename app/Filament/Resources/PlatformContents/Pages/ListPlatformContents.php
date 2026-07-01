<?php

namespace App\Filament\Resources\PlatformContents\Pages;

use App\Filament\Resources\PlatformContents\PlatformContentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPlatformContents extends ListRecords
{
    protected static string $resource = PlatformContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
