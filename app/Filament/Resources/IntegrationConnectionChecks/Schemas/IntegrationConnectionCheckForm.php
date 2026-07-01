<?php

namespace App\Filament\Resources\IntegrationConnectionChecks\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class IntegrationConnectionCheckForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('connection_id')
                    ->relationship('connection', 'name')
                    ->required(),
                TextInput::make('status')
                    ->required(),
                TextInput::make('latency_ms')
                    ->numeric()
                    ->default(null),
                TextInput::make('http_status')
                    ->numeric()
                    ->default(null),
                TextInput::make('message')
                    ->default(null),
                DateTimePicker::make('checked_at'),
                TextInput::make('checked_by')
                    ->numeric()
                    ->default(null),
            ]);
    }
}
