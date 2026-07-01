<?php

namespace App\Filament\Resources\AssetCategories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AssetCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                TextInput::make('code'),
                TextInput::make('name')
                    ->required(),
                Select::make('parent_id')
                    ->relationship('parent', 'name'),
            ]);
    }
}
