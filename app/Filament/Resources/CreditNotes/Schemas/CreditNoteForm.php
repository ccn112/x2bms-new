<?php

namespace App\Filament\Resources\CreditNotes\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CreditNoteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('credit_note_no')
                    ->required(),
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                Select::make('invoice_id')
                    ->relationship('invoice', 'id'),
                Select::make('adjustment_id')
                    ->relationship('adjustment', 'id'),
                TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('reason'),
                TextInput::make('status')
                    ->required()
                    ->default('issued'),
                DateTimePicker::make('issued_at'),
                DateTimePicker::make('applied_at'),
            ]);
    }
}
