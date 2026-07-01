<?php

namespace App\Filament\Resources\IntercomEvents\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class IntercomEventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                Select::make('building_id')
                    ->relationship('building', 'name'),
                Select::make('apartment_id')
                    ->relationship('apartment', 'id'),
                Select::make('resident_id')
                    ->relationship('resident', 'id'),
                TextInput::make('from_device'),
                TextInput::make('direction')
                    ->required()
                    ->default('incoming'),
                TextInput::make('status')
                    ->required()
                    ->default('answered'),
                TextInput::make('duration_seconds')
                    ->required()
                    ->numeric()
                    ->default(0),
                DateTimePicker::make('event_at'),
            ]);
    }
}
