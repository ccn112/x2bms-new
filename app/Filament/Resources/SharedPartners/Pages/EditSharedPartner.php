<?php

namespace App\Filament\Resources\SharedPartners\Pages;

use App\Filament\Resources\SharedPartners\SharedPartnerResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSharedPartner extends EditRecord
{
    protected static string $resource = SharedPartnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
