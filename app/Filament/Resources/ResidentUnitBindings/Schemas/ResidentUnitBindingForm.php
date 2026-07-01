<?php

namespace App\Filament\Resources\ResidentUnitBindings\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ResidentUnitBindingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user_account_id')
                    ->required()
                    ->numeric(),
                Select::make('tenant_id')
                    ->relationship('tenant', 'name'),
                TextInput::make('project_id')
                    ->numeric(),
                TextInput::make('building_id')
                    ->numeric(),
                Select::make('apartment_id')
                    ->relationship('apartment', 'id'),
                TextInput::make('role')
                    ->required()
                    ->default('owner'),
                TextInput::make('status')
                    ->required()
                    ->default('active'),
                DateTimePicker::make('starts_at'),
                DateTimePicker::make('ends_at'),
                TextInput::make('approved_request_id')
                    ->numeric(),
            ]);
    }
}
