<?php

namespace App\Filament\Resources\BillingPayments\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BillingPaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('invoice_id')
                    ->relationship('invoice', 'id')
                    ->required(),
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                TextInput::make('payment_method')
                    ->required()
                    ->default('bank_transfer'),
                TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                DateTimePicker::make('paid_at'),
                TextInput::make('transaction_ref'),
                TextInput::make('status')
                    ->required()
                    ->default('confirmed'),
                TextInput::make('reconciliation_id')
                    ->numeric(),
                TextInput::make('metadata_json'),
            ]);
    }
}
