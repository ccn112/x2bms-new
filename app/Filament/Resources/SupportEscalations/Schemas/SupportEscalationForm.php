<?php

namespace App\Filament\Resources\SupportEscalations\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SupportEscalationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('support_ticket_id')
                    ->required()
                    ->numeric(),
                TextInput::make('from_level')
                    ->default(null),
                TextInput::make('to_level')
                    ->required(),
                TextInput::make('reason')
                    ->default(null),
                TextInput::make('status')
                    ->required()
                    ->default('active'),
                TextInput::make('escalated_by')
                    ->numeric()
                    ->default(null),
                DateTimePicker::make('resolved_at'),
            ]);
    }
}
