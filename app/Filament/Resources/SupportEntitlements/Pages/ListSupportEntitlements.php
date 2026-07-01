<?php

namespace App\Filament\Resources\SupportEntitlements\Pages;

use App\Filament\Resources\SupportEntitlements\SupportEntitlementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSupportEntitlements extends ListRecords
{
    protected static string $resource = SupportEntitlementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
