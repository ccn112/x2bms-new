<?php

namespace App\Filament\Resources\MaintenancePlans\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MaintenancePlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                TextInput::make('project_id')
                    ->numeric(),
                Select::make('asset_id')
                    ->relationship('asset', 'name'),
                Select::make('team_id')
                    ->relationship('team', 'name'),
                TextInput::make('name')
                    ->required(),
                TextInput::make('frequency')
                    ->required()
                    ->default('monthly'),
                DateTimePicker::make('next_due_at'),
                TextInput::make('status')
                    ->required()
                    ->default('active'),
            ]);
    }
}
