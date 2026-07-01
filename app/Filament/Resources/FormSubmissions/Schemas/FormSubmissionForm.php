<?php

namespace App\Filament\Resources\FormSubmissions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class FormSubmissionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                TextInput::make('dynamic_form_id')
                    ->required()
                    ->numeric(),
                TextInput::make('submitted_by_id')
                    ->numeric(),
                Select::make('resident_id')
                    ->relationship('resident', 'id'),
                TextInput::make('status')
                    ->required()
                    ->default('submitted'),
                TextInput::make('data'),
                DateTimePicker::make('submitted_at'),
            ]);
    }
}
