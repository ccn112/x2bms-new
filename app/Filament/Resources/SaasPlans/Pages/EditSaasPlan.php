<?php

namespace App\Filament\Resources\SaasPlans\Pages;

use App\Filament\Resources\SaasPlans\SaasPlanResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSaasPlan extends EditRecord
{
    protected static string $resource = SaasPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
