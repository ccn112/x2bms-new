<?php

namespace App\Filament\Resources\IntegrationMappings\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class IntegrationMappingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('connection_id')
                    ->relationship('connection', 'name')
                    ->required(),
                TextInput::make('mapping_type')
                    ->required(),
                TextInput::make('source_event')
                    ->default(null),
                TextInput::make('target_event')
                    ->default(null),
                Textarea::make('mapping_json')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('version')
                    ->required()
                    ->numeric()
                    ->default(1),
                TextInput::make('status')
                    ->required()
                    ->default('active'),
                TextInput::make('created_by')
                    ->numeric()
                    ->default(null),
                TextInput::make('updated_by')
                    ->numeric()
                    ->default(null),
            ]);
    }
}
