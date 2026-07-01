<?php

namespace App\Filament\Resources\SlaPolicies\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SlaPolicyForm
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
                TextInput::make('name')
                    ->required(),
                TextInput::make('applies_to')
                    ->required()
                    ->default('feedback_request'),
                TextInput::make('priority'),
                TextInput::make('response_minutes')
                    ->required()
                    ->numeric()
                    ->default(60),
                TextInput::make('resolve_minutes')
                    ->required()
                    ->numeric()
                    ->default(1440),
                Toggle::make('business_hours_only')
                    ->required(),
                TextInput::make('status')
                    ->required()
                    ->default('active'),
            ]);
    }
}
