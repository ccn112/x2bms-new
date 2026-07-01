<?php

namespace App\Filament\Resources\SubscriptionContracts\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SubscriptionContractForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                TextInput::make('contract_no')
                    ->required(),
                TextInput::make('contract_type')
                    ->required()
                    ->default('standard'),
                TextInput::make('status')
                    ->required()
                    ->default('draft'),
                TextInput::make('file_url')
                    ->url(),
                DatePicker::make('start_date'),
                DatePicker::make('end_date'),
                TextInput::make('annual_value')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('payment_terms'),
                TextInput::make('sla_code'),
                TextInput::make('responsible_user_id')
                    ->numeric(),
                TextInput::make('metadata_json'),
            ]);
    }
}
