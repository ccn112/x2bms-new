<?php

namespace App\Filament\Resources\CashVouchers\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CashVoucherForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                Select::make('project_id')
                    ->relationship('project', 'name'),
                Select::make('fund_id')
                    ->relationship('fund', 'name'),
                Select::make('payment_request_id')
                    ->relationship('paymentRequest', 'title'),
                TextInput::make('code'),
                TextInput::make('type')
                    ->required()
                    ->default('payment'),
                TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('party'),
                TextInput::make('description'),
                DatePicker::make('voucher_date'),
                TextInput::make('status')
                    ->required()
                    ->default('posted'),
                TextInput::make('created_by_id')
                    ->numeric(),
            ]);
    }
}
