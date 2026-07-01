<?php

namespace App\Filament\Resources\SupportTickets\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class SupportTicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                TextInput::make('code'),
                TextInput::make('subject')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('category'),
                TextInput::make('priority')
                    ->required()
                    ->default('normal'),
                TextInput::make('status')
                    ->required()
                    ->default('open'),
                TextInput::make('channel')
                    ->required()
                    ->default('web'),
                Select::make('requester_id')
                    ->relationship('requester', 'name'),
                Select::make('assignee_id')
                    ->relationship('assignee', 'name'),
                DateTimePicker::make('resolved_at'),
            ]);
    }
}
