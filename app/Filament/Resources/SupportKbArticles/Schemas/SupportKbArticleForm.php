<?php

namespace App\Filament\Resources\SupportKbArticles\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Schema;

class SupportKbArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required(),
                TextInput::make('title')
                    ->required(),
                Select::make('category_id')
                    ->relationship('category', 'name')
                    ->default(null),
                RichEditor::make('body')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('status')
                    ->required()
                    ->default('draft'),
                TextInput::make('rating')
                    ->numeric()
                    ->default(null),
                TextInput::make('views')
                    ->required()
                    ->numeric()
                    ->default(0),
                Select::make('author_id')
                    ->relationship('author', 'name')
                    ->default(null),
                DateTimePicker::make('published_at'),
            ]);
    }
}
