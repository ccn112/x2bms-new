<?php

namespace App\Filament\Resources\SupportSlaPolicies\Pages;

use App\Filament\Resources\SupportSlaPolicies\SupportSlaPolicyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSupportSlaPolicies extends ListRecords
{
    protected static string $resource = SupportSlaPolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
