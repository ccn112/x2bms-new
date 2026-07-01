<?php

namespace App\Filament\Resources\PlatformContents\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class PlatformContentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category_id')
                    ->relationship('category', 'name'),
                TextInput::make('title')
                    ->required(),
                TextInput::make('slug'),
                TextInput::make('summary'),
                Textarea::make('body')
                    ->columnSpanFull(),
                FileUpload::make('cover_image')
                    ->image(),
                TextInput::make('content_type')
                    ->required()
                    ->default('news'),
                TextInput::make('publish_scope')
                    ->required()
                    ->default('platform'),
                TextInput::make('status')
                    ->required()
                    ->default('draft'),
                TextInput::make('language')
                    ->required()
                    ->default('vi'),
                DateTimePicker::make('published_at'),
                DateTimePicker::make('expired_at'),
                TextInput::make('created_by')
                    ->numeric(),
                TextInput::make('approved_by')
                    ->numeric(),
                TextInput::make('metadata_json'),
            ]);
    }
}
