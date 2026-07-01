<?php

namespace App\Filament\Resources\DocumentTemplateCategories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DocumentTemplateCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('description'),
                Select::make('parent_id')
                    ->relationship('parent', 'name'),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
