<?php

namespace App\Filament\Resources\ServiceOrders\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ServiceOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                TextInput::make('service_provider_id')
                    ->numeric(),
                Select::make('resident_id')
                    ->relationship('resident', 'id'),
                TextInput::make('code'),
                TextInput::make('description'),
                TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
                DateTimePicker::make('scheduled_at'),
            ]);
    }
}
