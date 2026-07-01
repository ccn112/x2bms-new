<?php

namespace App\Filament\Resources\SaasPlans\Pages;

use App\Filament\Resources\SaasPlans\SaasPlanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSaasPlans extends ListRecords
{
    protected static string $resource = SaasPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
