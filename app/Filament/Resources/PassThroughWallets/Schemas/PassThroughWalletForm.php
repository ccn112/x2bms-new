<?php

namespace App\Filament\Resources\PassThroughWallets\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PassThroughWalletForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                TextInput::make('wallet_type')
                    ->required(),
                TextInput::make('balance')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('currency')
                    ->required()
                    ->default('VND'),
                TextInput::make('monthly_target')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('low_balance_threshold')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                Toggle::make('auto_topup_enabled')
                    ->required(),
                TextInput::make('auto_topup_amount')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('status')
                    ->required()
                    ->default('active'),
            ]);
    }
}
