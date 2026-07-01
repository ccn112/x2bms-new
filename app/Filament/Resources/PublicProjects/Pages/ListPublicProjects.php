<?php

namespace App\Filament\Resources\PublicProjects\Pages;

use App\Filament\Resources\PublicProjects\PublicProjectResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPublicProjects extends ListRecords
{
    protected static string $resource = PublicProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
