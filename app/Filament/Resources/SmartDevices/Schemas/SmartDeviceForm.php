<?php

namespace App\Filament\Resources\SmartDevices\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SmartDeviceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('smart_home_account_id')
                    ->required()
                    ->numeric(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('type')
                    ->required()
                    ->default('light'),
                TextInput::make('room'),
                TextInput::make('status')
                    ->required()
                    ->default('offline'),
            ]);
    }
}
