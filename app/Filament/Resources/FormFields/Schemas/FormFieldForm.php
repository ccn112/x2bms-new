<?php

namespace App\Filament\Resources\FormFields\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class FormFieldForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('dynamic_form_id')
                    ->required()
                    ->numeric(),
                TextInput::make('form_section_id')
                    ->numeric(),
                TextInput::make('key')
                    ->required(),
                TextInput::make('label')
                    ->required(),
                TextInput::make('type')
                    ->required()
                    ->default('text'),
                TextInput::make('options'),
                Toggle::make('required')
                    ->required(),
                TextInput::make('sort')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('config'),
            ]);
    }
}
