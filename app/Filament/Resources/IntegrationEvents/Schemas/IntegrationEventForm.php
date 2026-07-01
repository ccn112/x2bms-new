<?php

namespace App\Filament\Resources\IntegrationEvents\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class IntegrationEventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('event_id')
                    ->required(),
                TextInput::make('correlation_id')
                    ->default(null),
                TextInput::make('source')
                    ->required(),
                TextInput::make('event_type')
                    ->required(),
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->default(null),
                TextInput::make('status')
                    ->required()
                    ->default('success'),
                TextInput::make('duration_ms')
                    ->numeric()
                    ->default(null),
                TextInput::make('retry_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('payload_hash')
                    ->default(null),
                Textarea::make('message')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}
