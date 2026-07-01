<?php

namespace App\Filament\Resources\BillingReconciliations\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BillingReconciliationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                Select::make('invoice_id')
                    ->relationship('invoice', 'id'),
                Select::make('payment_id')
                    ->relationship('payment', 'id'),
                TextInput::make('bank_transaction_ref'),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
                TextInput::make('difference_amount')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('confirmed_by')
                    ->numeric(),
                DateTimePicker::make('confirmed_at'),
            ]);
    }
}
