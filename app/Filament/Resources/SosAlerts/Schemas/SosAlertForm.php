<?php

namespace App\Filament\Resources\SosAlerts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SosAlertForm
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
                TextInput::make('source')
                    ->required()
                    ->default('app'),
                TextInput::make('status')
                    ->required()
                    ->default('triggered'),
                TextInput::make('location'),
                DateTimePicker::make('triggered_at'),
                Select::make('acknowledged_by_id')
                    ->relationship('acknowledgedBy', 'name'),
                DateTimePicker::make('resolved_at'),
                TextInput::make('note'),
            ]);
    }
}
