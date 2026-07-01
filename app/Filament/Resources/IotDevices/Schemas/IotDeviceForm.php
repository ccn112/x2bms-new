<?php

namespace App\Filament\Resources\IotDevices\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class IotDeviceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                Select::make('project_id')
                    ->relationship('project', 'name'),
                Select::make('building_id')
                    ->relationship('building', 'name'),
                TextInput::make('code'),
                TextInput::make('name')
                    ->required(),
                TextInput::make('type')
                    ->required()
                    ->default('sensor'),
                TextInput::make('protocol'),
                TextInput::make('status')
                    ->required()
                    ->default('online'),
                DateTimePicker::make('last_seen_at'),
            ]);
    }
}
