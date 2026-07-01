<?php

namespace App\Filament\Resources\TenantProjectLinks\Pages;

use App\Filament\Resources\TenantProjectLinks\TenantProjectLinkResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTenantProjectLinks extends ListRecords
{
    protected static string $resource = TenantProjectLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
