<?php

namespace App\Filament\Resources\AiApprovals\Pages;

use App\Filament\Resources\AiApprovals\AiApprovalResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAiApproval extends EditRecord
{
    protected static string $resource = AiApprovalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
