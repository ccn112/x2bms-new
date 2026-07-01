<?php

namespace App\Filament\Resources\SubscriptionAddons\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SubscriptionAddonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('subscription_id')
                    ->relationship('subscription', 'id')
                    ->required(),
                TextInput::make('addon_code')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('quantity')
                    ->required()
                    ->numeric()
                    ->default(1.0),
                TextInput::make('unit_price')
                    ->required()
                    ->numeric()
                    ->default(0.0)
                    ->prefix('$'),
                TextInput::make('mrr')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('wallet_type'),
                TextInput::make('status')
                    ->required()
                    ->default('active'),
                DatePicker::make('start_date'),
                DatePicker::make('end_date'),
            ]);
    }
}
