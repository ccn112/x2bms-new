<?php

namespace App\Filament\Resources\SmartHomeAccounts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SmartHomeAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                Select::make('resident_id')
                    ->relationship('resident', 'id'),
                TextInput::make('apartment_id')
                    ->numeric(),
                TextInput::make('provider'),
                TextInput::make('status')
                    ->required()
                    ->default('active'),
                DateTimePicker::make('linked_at'),
            ]);
    }
}
