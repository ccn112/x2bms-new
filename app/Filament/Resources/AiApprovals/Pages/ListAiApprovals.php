<?php

namespace App\Filament\Resources\AiApprovals\Pages;

use App\Filament\Resources\AiApprovals\AiApprovalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAiApprovals extends ListRecords
{
    protected static string $resource = AiApprovalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
