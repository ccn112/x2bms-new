<?php

namespace App\Filament\Resources\IntercomEvents\Pages;

use App\Filament\Resources\IntercomEvents\IntercomEventResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditIntercomEvent extends EditRecord
{
    protected static string $resource = IntercomEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
