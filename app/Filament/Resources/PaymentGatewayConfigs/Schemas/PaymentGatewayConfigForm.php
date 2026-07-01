<?php

namespace App\Filament\Resources\PaymentGatewayConfigs\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PaymentGatewayConfigForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                TextInput::make('gateway')
                    ->required(),
                TextInput::make('merchant_id'),
                TextInput::make('environment')
                    ->required()
                    ->default('sandbox'),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('config'),
            ]);
    }
}
