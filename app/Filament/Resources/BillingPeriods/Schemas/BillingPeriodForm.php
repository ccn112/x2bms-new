<?php

namespace App\Filament\Resources\BillingPeriods\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BillingPeriodForm
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
                TextInput::make('code')
                    ->required(),
                TextInput::make('label')
                    ->required(),
                DatePicker::make('period_month')
                    ->required(),
                TextInput::make('billed_amount')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('collected_amount')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                Toggle::make('is_current')
                    ->required(),
                TextInput::make('status')
                    ->required()
                    ->default('open'),
                DatePicker::make('due_date'),
            ]);
    }
}
