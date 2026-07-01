<?php

namespace App\Filament\Resources\AiRequests\Pages;

use App\Filament\Resources\AiRequests\AiRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAiRequest extends EditRecord
{
    protected static string $resource = AiRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
