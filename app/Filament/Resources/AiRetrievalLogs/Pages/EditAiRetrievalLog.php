<?php

namespace App\Filament\Resources\AiRetrievalLogs\Pages;

use App\Filament\Resources\AiRetrievalLogs\AiRetrievalLogResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAiRetrievalLog extends EditRecord
{
    protected static string $resource = AiRetrievalLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
