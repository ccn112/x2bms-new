<?php

namespace App\Filament\Resources\PublicProjects\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PublicProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('developer_name'),
                TextInput::make('address'),
                TextInput::make('province'),
                TextInput::make('project_type'),
                TextInput::make('status')
                    ->required()
                    ->default('operating'),
                TextInput::make('blocks')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('apartments')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('amenities_json'),
                Textarea::make('description')
                    ->columnSpanFull(),
                Toggle::make('is_public')
                    ->required(),
                TextInput::make('metadata_json'),
            ]);
    }
}
