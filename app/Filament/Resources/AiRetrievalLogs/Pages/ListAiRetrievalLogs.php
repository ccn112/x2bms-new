<?php

namespace App\Filament\Resources\AiRetrievalLogs\Pages;

use App\Filament\Resources\AiRetrievalLogs\AiRetrievalLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAiRetrievalLogs extends ListRecords
{
    protected static string $resource = AiRetrievalLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
