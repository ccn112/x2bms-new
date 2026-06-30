<?php

namespace App\Filament\Resources\Statements\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class StatementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                TextInput::make('building_id')
                    ->required()
                    ->numeric(),
                TextInput::make('billing_period_id')
                    ->required()
                    ->numeric(),
                TextInput::make('apartment_id')
                    ->required()
                    ->numeric(),
                TextInput::make('code'),
                TextInput::make('total_amount')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('paid_amount')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('status')
                    ->required()
                    ->default('issued'),
                DateTimePicker::make('issued_at'),
                DateTimePicker::make('published_at'),
            ]);
    }
}
