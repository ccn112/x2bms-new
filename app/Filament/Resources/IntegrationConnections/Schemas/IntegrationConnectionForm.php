<?php

namespace App\Filament\Resources\IntegrationConnections\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class IntegrationConnectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                Select::make('category_id')
                    ->relationship('category', 'name')
                    ->default(null),
                TextInput::make('provider_code')
                    ->default(null),
                TextInput::make('environment')
                    ->required()
                    ->default('production'),
                TextInput::make('status')
                    ->required()
                    ->default('active'),
                TextInput::make('api_version')
                    ->default(null),
                TextInput::make('base_url')
                    ->url()
                    ->default(null),
                TextInput::make('owner_user_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('timeout_seconds')
                    ->required()
                    ->numeric()
                    ->default(30),
                TextInput::make('retry_policy')
                    ->default(null),
                Toggle::make('idempotency_enabled')
                    ->required(),
                DateTimePicker::make('last_checked_at'),
                TextInput::make('success_rate_24h')
                    ->numeric()
                    ->default(null),
                TextInput::make('avg_latency_ms')
                    ->numeric()
                    ->default(null),
                TextInput::make('sla_status')
                    ->required()
                    ->default('healthy'),
                Textarea::make('metadata_json')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}
