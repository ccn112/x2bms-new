<?php

namespace App\Filament\Resources\Meters\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MeterForm
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
                TextInput::make('building_id')
                    ->numeric(),
                Select::make('apartment_id')
                    ->relationship('apartment', 'id'),
                TextInput::make('code'),
                TextInput::make('type')
                    ->required()
                    ->default('electric'),
                TextInput::make('unit')
                    ->required()
                    ->default('kWh'),
                TextInput::make('last_reading')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('status')
                    ->required()
                    ->default('active'),
                DatePicker::make('installed_at'),
            ]);
    }
}
