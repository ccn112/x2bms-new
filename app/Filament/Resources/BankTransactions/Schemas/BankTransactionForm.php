<?php

namespace App\Filament\Resources\BankTransactions\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BankTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                Select::make('bank_account_id')
                    ->relationship('bankAccount', 'id'),
                TextInput::make('bank_statement_import_id')
                    ->numeric(),
                DatePicker::make('txn_date'),
                TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('direction')
                    ->required()
                    ->default('credit'),
                TextInput::make('description'),
                TextInput::make('reference_no'),
                Toggle::make('is_matched')
                    ->required(),
                Select::make('payment_id')
                    ->relationship('payment', 'id'),
            ]);
    }
}
