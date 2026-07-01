<?php

namespace App\Filament\Resources\SharedPartners\Pages;

use App\Filament\Resources\SharedPartners\SharedPartnerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSharedPartners extends ListRecords
{
    protected static string $resource = SharedPartnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
