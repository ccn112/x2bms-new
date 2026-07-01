<?php

namespace App\Filament\Resources\AiGuardrailPolicies\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AiGuardrailPolicyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('description'),
                TextInput::make('policy_type')
                    ->required()
                    ->default('privacy'),
                TextInput::make('rule_json'),
                TextInput::make('severity')
                    ->required()
                    ->default('medium'),
                TextInput::make('action')
                    ->required()
                    ->default('warn'),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
