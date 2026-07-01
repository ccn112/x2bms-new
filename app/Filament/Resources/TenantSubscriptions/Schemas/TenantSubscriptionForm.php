<?php

namespace App\Filament\Resources\TenantSubscriptions\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TenantSubscriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                Select::make('plan_id')
                    ->relationship('plan', 'name'),
                TextInput::make('status')
                    ->required()
                    ->default('active'),
                TextInput::make('billing_cycle')
                    ->required()
                    ->default('monthly'),
                DatePicker::make('start_date'),
                DatePicker::make('end_date'),
                Toggle::make('auto_renew')
                    ->required(),
                TextInput::make('mrr')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('arr')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('currency')
                    ->required()
                    ->default('VND'),
                Select::make('contract_id')
                    ->relationship('contract', 'id'),
                TextInput::make('owner_user_id')
                    ->numeric(),
                TextInput::make('metadata_json'),
            ]);
    }
}
