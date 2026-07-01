<?php

namespace App\Filament\Resources\SecurityIncidents\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class SecurityIncidentForm
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
                TextInput::make('type')
                    ->required()
                    ->default('other'),
                TextInput::make('severity')
                    ->required()
                    ->default('medium'),
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('location'),
                TextInput::make('status')
                    ->required()
                    ->default('open'),
                TextInput::make('reported_by_id')
                    ->numeric(),
                DateTimePicker::make('occurred_at'),
                DateTimePicker::make('resolved_at'),
            ]);
    }
}
