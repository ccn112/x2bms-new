<?php

namespace App\Filament\Resources\SupportSlaPolicies\Pages;

use App\Filament\Resources\SupportSlaPolicies\SupportSlaPolicyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSupportSlaPolicy extends EditRecord
{
    protected static string $resource = SupportSlaPolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
