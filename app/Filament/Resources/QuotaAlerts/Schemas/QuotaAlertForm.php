<?php

namespace App\Filament\Resources\QuotaAlerts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class QuotaAlertForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code'),
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                TextInput::make('usage_period_id')
                    ->numeric(),
                TextInput::make('meter_type')
                    ->required(),
                TextInput::make('usage_value')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('included_limit')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('over_percent')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('estimated_fee')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('recommendation'),
                TextInput::make('status')
                    ->required()
                    ->default('open'),
                TextInput::make('owner_user_id')
                    ->numeric(),
                DateTimePicker::make('resolved_at'),
            ]);
    }
}
