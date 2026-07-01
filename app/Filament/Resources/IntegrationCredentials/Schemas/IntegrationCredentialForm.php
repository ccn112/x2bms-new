<?php

namespace App\Filament\Resources\IntegrationCredentials\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class IntegrationCredentialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('connection_id')
                    ->relationship('connection', 'name')
                    ->required(),
                TextInput::make('credential_type')
                    ->required(),
                TextInput::make('masked_summary')
                    ->default(null),
                TextInput::make('status')
                    ->required()
                    ->default('valid'),
                DateTimePicker::make('expires_at'),
                DateTimePicker::make('rotated_at'),
                TextInput::make('rotated_by')
                    ->numeric()
                    ->default(null),
                TextInput::make('created_by')
                    ->numeric()
                    ->default(null),
            ]);
    }
}
