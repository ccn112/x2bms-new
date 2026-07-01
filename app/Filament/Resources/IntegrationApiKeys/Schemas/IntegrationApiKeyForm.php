<?php

namespace App\Filament\Resources\IntegrationApiKeys\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class IntegrationApiKeyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('client_id')
                    ->required(),
                TextInput::make('environment')
                    ->required()
                    ->default('production'),
                TextInput::make('status')
                    ->required()
                    ->default('draft'),
                DatePicker::make('expires_at'),
                DateTimePicker::make('last_used_at'),
                TextInput::make('owner_user_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('rate_limit_per_minute')
                    ->required()
                    ->numeric()
                    ->default(600),
                Toggle::make('require_hmac')
                    ->required(),
                Toggle::make('require_ip_allowlist')
                    ->required(),
                Textarea::make('allowed_ips_json')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('metadata_json')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}
