<?php

namespace App\Filament\Resources\Contracts\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ContractForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                Select::make('project_id')
                    ->relationship('project', 'name'),
                Select::make('contractor_id')
                    ->relationship('contractor', 'name'),
                TextInput::make('code'),
                TextInput::make('title')
                    ->required(),
                TextInput::make('type')
                    ->required()
                    ->default('service'),
                TextInput::make('value')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                DatePicker::make('start_date'),
                DatePicker::make('end_date'),
                TextInput::make('status')
                    ->required()
                    ->default('active'),
                TextInput::make('file_path'),
            ]);
    }
}
