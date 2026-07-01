<?php

namespace App\Filament\Resources\AccessDevices\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AccessDeviceForm
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
                    ->default('card_reader'),
                TextInput::make('location'),
                TextInput::make('ip_address'),
                TextInput::make('status')
                    ->required()
                    ->default('online'),
                DateTimePicker::make('last_sync_at'),
            ]);
    }
}
