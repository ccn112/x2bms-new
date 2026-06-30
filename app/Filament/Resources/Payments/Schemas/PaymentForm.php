<?php

namespace App\Filament\Resources\Payments\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                TextInput::make('building_id')
                    ->numeric(),
                Select::make('apartment_id')
                    ->relationship('apartment', 'id'),
                Select::make('resident_id')
                    ->relationship('resident', 'id'),
                TextInput::make('payment_method_id')
                    ->numeric(),
                TextInput::make('code')
                    ->required(),
                TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                DateTimePicker::make('paid_at'),
                TextInput::make('reference_no'),
                TextInput::make('status')
                    ->required()
                    ->default('confirmed'),
                TextInput::make('note'),
            ]);
    }
}
