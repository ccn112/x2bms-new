<?php

namespace App\Filament\Resources\IntegrationIncidents\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class IntegrationIncidentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required(),
                TextInput::make('title')
                    ->required(),
                TextInput::make('severity')
                    ->required()
                    ->default('medium'),
                TextInput::make('status')
                    ->required()
                    ->default('open'),
                TextInput::make('source')
                    ->default(null),
                DateTimePicker::make('started_at'),
                DateTimePicker::make('resolved_at'),
                TextInput::make('owner_user_id')
                    ->numeric()
                    ->default(null),
                Textarea::make('summary')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('root_cause')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}
