<?php

namespace App\Filament\Resources\QrPaymentTokens\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class QrPaymentTokenForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                Select::make('statement_id')
                    ->relationship('statement', 'id'),
                Select::make('debt_id')
                    ->relationship('debt', 'id'),
                Select::make('payment_id')
                    ->relationship('payment', 'id'),
                TextInput::make('code')
                    ->required(),
                TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('provider')
                    ->required()
                    ->default('vietqr'),
                TextInput::make('status')
                    ->required()
                    ->default('active'),
                DateTimePicker::make('expires_at'),
                DateTimePicker::make('paid_at'),
            ]);
    }
}
