<?php

namespace App\Filament\Resources\DataFixRequests\Pages;

use App\Filament\Resources\DataFixRequests\DataFixRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDataFixRequests extends ListRecords
{
    protected static string $resource = DataFixRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
