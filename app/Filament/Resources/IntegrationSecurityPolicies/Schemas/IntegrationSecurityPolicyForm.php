<?php

namespace App\Filament\Resources\IntegrationSecurityPolicies\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class IntegrationSecurityPolicyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('policy_key')
                    ->required(),
                Textarea::make('policy_value_json')
                    ->default(null)
                    ->columnSpanFull(),
                Toggle::make('is_enabled')
                    ->required(),
                TextInput::make('updated_by')
                    ->numeric()
                    ->default(null),
            ]);
    }
}
