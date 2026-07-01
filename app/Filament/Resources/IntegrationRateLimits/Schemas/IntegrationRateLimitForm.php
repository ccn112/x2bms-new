<?php

namespace App\Filament\Resources\IntegrationRateLimits\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class IntegrationRateLimitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('scope_type')
                    ->required()
                    ->default('global'),
                TextInput::make('scope_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('limit_per_minute')
                    ->required()
                    ->numeric()
                    ->default(1000),
                TextInput::make('burst_limit')
                    ->numeric()
                    ->default(null),
                TextInput::make('window_seconds')
                    ->required()
                    ->numeric()
                    ->default(60),
                Toggle::make('is_enabled')
                    ->required(),
            ]);
    }
}
