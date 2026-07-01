<?php

namespace App\Filament\Resources\DocumentTemplates\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DocumentTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required(),
                Select::make('category_id')
                    ->relationship('category', 'name'),
                TextInput::make('title')
                    ->required(),
                TextInput::make('description'),
                TextInput::make('template_type')
                    ->required()
                    ->default('notice'),
                TextInput::make('owner_scope')
                    ->required()
                    ->default('platform'),
                TextInput::make('owner_id')
                    ->numeric(),
                TextInput::make('version')
                    ->required()
                    ->numeric()
                    ->default(1),
                TextInput::make('status')
                    ->required()
                    ->default('active'),
                TextInput::make('file_url')
                    ->url(),
                Textarea::make('body_markdown')
                    ->columnSpanFull(),
                TextInput::make('variables_json'),
                Toggle::make('ai_readable')
                    ->required(),
                DatePicker::make('effective_from'),
                DatePicker::make('effective_to'),
                TextInput::make('created_by')
                    ->numeric(),
                TextInput::make('approved_by')
                    ->numeric(),
            ]);
    }
}
