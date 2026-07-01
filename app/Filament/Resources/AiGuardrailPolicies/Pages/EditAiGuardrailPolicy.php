<?php

namespace App\Filament\Resources\AiGuardrailPolicies\Pages;

use App\Filament\Resources\AiGuardrailPolicies\AiGuardrailPolicyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAiGuardrailPolicy extends EditRecord
{
    protected static string $resource = AiGuardrailPolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
