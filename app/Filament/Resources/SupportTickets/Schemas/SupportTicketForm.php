<?php

namespace App\Filament\Resources\SupportTickets\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Schema;

class SupportTicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('ticket_no')
                    ->required(),
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->default(null),
                TextInput::make('subject')
                    ->required(),
                RichEditor::make('description')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('module')
                    ->default(null),
                TextInput::make('category')
                    ->default(null),
                TextInput::make('priority')
                    ->required()
                    ->default('medium'),
                TextInput::make('status')
                    ->required()
                    ->default('new'),
                TextInput::make('environment')
                    ->required()
                    ->default('production'),
                TextInput::make('channel')
                    ->required()
                    ->default('web'),
                Select::make('sla_policy_id')
                    ->relationship('slaPolicy', 'name')
                    ->default(null),
                TextInput::make('sla_state')
                    ->required()
                    ->default('within_sla'),
                DateTimePicker::make('sla_due_at'),
                DateTimePicker::make('first_response_at'),
                DateTimePicker::make('resolved_at'),
                DateTimePicker::make('closed_at'),
                Select::make('owner_id')
                    ->relationship('owner', 'name')
                    ->default(null),
                Select::make('team_id')
                    ->relationship('team', 'name')
                    ->default(null),
                TextInput::make('requester_name')
                    ->default(null),
                TextInput::make('requester_contact')
                    ->default(null),
                TextInput::make('csat_score')
                    ->numeric()
                    ->default(null),
                RichEditor::make('resolution_summary')
                    ->default(null)
                    ->columnSpanFull(),
                RichEditor::make('tags')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('reopen_count')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
