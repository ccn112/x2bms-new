<?php

namespace App\Filament\Resources\Polls\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PollForm
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
                TextInput::make('question')
                    ->required(),
                TextInput::make('type')
                    ->required()
                    ->default('single'),
                TextInput::make('status')
                    ->required()
                    ->default('open'),
                DateTimePicker::make('closes_at'),
                TextInput::make('vote_count')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
