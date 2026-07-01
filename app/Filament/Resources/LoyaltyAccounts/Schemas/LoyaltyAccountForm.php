<?php

namespace App\Filament\Resources\LoyaltyAccounts\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LoyaltyAccountForm
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
                TextInput::make('points_balance')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('tier')
                    ->required()
                    ->default('silver'),
                TextInput::make('status')
                    ->required()
                    ->default('active'),
            ]);
    }
}
