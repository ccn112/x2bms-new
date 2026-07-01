<?php

namespace App\Filament\Resources\IntegrationApiKeyScopes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class IntegrationApiKeyScopeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('api_key_id')
                    ->relationship('apiKey', 'name')
                    ->required(),
                TextInput::make('scope_code')
                    ->required(),
                TextInput::make('scope_name')
                    ->default(null),
                TextInput::make('permission_level')
                    ->default(null),
            ]);
    }
}
