<?php

namespace App\Filament\Resources\FeeRates\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class FeeRateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                Select::make('fee_type_id')
                    ->relationship('feeType', 'name')
                    ->required(),
                TextInput::make('code')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('unit'),
                DatePicker::make('effective_from'),
                DatePicker::make('effective_to'),
                TextInput::make('status')
                    ->required()
                    ->default('active'),
                TextInput::make('note'),
            ]);
    }
}
