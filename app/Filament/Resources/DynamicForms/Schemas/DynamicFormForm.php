<?php

namespace App\Filament\Resources\DynamicForms\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DynamicFormForm
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
                TextInput::make('code'),
                TextInput::make('name')
                    ->required(),
                TextInput::make('description'),
                TextInput::make('category'),
                TextInput::make('status')
                    ->required()
                    ->default('draft'),
                TextInput::make('current_version')
                    ->required()
                    ->numeric()
                    ->default(1),
                TextInput::make('created_by_id')
                    ->numeric(),
            ]);
    }
}
