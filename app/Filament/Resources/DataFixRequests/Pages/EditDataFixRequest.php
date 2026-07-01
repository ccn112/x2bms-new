<?php

namespace App\Filament\Resources\DataFixRequests\Pages;

use App\Filament\Resources\DataFixRequests\DataFixRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDataFixRequest extends EditRecord
{
    protected static string $resource = DataFixRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
