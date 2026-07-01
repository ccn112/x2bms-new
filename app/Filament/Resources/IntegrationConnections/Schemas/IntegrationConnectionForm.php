<?php

namespace App\Filament\Resources\IntegrationConnections\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class IntegrationConnectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                TextInput::make('provider')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('status')
                    ->required()
                    ->default('connected'),
                TextInput::make('config'),
                DateTimePicker::make('last_sync_at'),
            ]);
    }
}
