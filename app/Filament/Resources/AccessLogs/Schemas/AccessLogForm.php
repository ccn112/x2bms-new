<?php

namespace App\Filament\Resources\AccessLogs\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AccessLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                TextInput::make('project_id')
                    ->numeric(),
                Select::make('building_id')
                    ->relationship('building', 'name'),
                Select::make('apartment_id')
                    ->relationship('apartment', 'id'),
                Select::make('resident_id')
                    ->relationship('resident', 'id'),
                TextInput::make('visitor_pass_id')
                    ->numeric(),
                Select::make('access_card_id')
                    ->relationship('accessCard', 'id'),
                TextInput::make('device_name'),
                TextInput::make('gate'),
                TextInput::make('direction')
                    ->required()
                    ->default('in'),
                TextInput::make('method')
                    ->required()
                    ->default('card'),
                TextInput::make('status')
                    ->required()
                    ->default('granted'),
                DateTimePicker::make('event_at'),
            ]);
    }
}
