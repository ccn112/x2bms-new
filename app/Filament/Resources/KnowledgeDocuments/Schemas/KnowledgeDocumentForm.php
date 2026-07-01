<?php

namespace App\Filament\Resources\KnowledgeDocuments\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class KnowledgeDocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required(),
                TextInput::make('title')
                    ->required(),
                TextInput::make('description'),
                TextInput::make('document_type')
                    ->required()
                    ->default('policy'),
                TextInput::make('owner_scope')
                    ->required()
                    ->default('platform'),
                TextInput::make('owner_id')
                    ->numeric(),
                Select::make('source_template_id')
                    ->relationship('sourceTemplate', 'title'),
                TextInput::make('file_url')
                    ->url(),
                Textarea::make('content_markdown')
                    ->columnSpanFull(),
                TextInput::make('language')
                    ->required()
                    ->default('vi'),
                TextInput::make('status')
                    ->required()
                    ->default('active'),
                TextInput::make('ai_index_status')
                    ->required()
                    ->default('not_indexed'),
                DateTimePicker::make('ai_indexed_at'),
                TextInput::make('version')
                    ->required()
                    ->numeric()
                    ->default(1),
                DatePicker::make('effective_from'),
                DatePicker::make('effective_to'),
                TextInput::make('sensitivity')
                    ->required()
                    ->default('internal'),
                TextInput::make('metadata_json'),
            ]);
    }
}
