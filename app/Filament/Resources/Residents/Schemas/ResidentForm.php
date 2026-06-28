<?php

namespace App\Filament\Resources\Residents\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ResidentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                TextInput::make('building_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('user_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('code')
                    ->required(),
                TextInput::make('full_name')
                    ->required(),
                TextInput::make('phone')
                    ->tel()
                    ->default(null),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->default(null),
                TextInput::make('id_no')
                    ->default(null),
                TextInput::make('status')
                    ->required()
                    ->default('active'),
            ]);
    }
}
