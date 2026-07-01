<?php

namespace App\Filament\Resources\PatrolRoutes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PatrolRouteForm
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
                Select::make('building_id')
                    ->relationship('building', 'name'),
                TextInput::make('code'),
                TextInput::make('name')
                    ->required(),
                TextInput::make('description'),
                TextInput::make('expected_minutes')
                    ->required()
                    ->numeric()
                    ->default(30),
                TextInput::make('status')
                    ->required()
                    ->default('active'),
            ]);
    }
}
