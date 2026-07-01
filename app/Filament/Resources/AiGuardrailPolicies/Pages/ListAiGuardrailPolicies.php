<?php

namespace App\Filament\Resources\AiGuardrailPolicies\Pages;

use App\Filament\Resources\AiGuardrailPolicies\AiGuardrailPolicyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAiGuardrailPolicies extends ListRecords
{
    protected static string $resource = AiGuardrailPolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
