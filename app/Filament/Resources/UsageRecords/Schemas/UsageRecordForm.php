<?php

namespace App\Filament\Resources\UsageRecords\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UsageRecordForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('usage_period_id')
                    ->required()
                    ->numeric(),
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
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
                TextInput::make('overage_value')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('overage_amount')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('source')
                    ->required()
                    ->default('collected'),
                TextInput::make('status')
                    ->required()
                    ->default('draft'),
                TextInput::make('metadata_json'),
            ]);
    }
}
