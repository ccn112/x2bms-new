<?php

namespace App\Filament\Resources\Assets\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AssetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                Select::make('project_id')
                    ->relationship('project', 'name'),
                Select::make('building_id')
                    ->relationship('building', 'name'),
                TextInput::make('asset_category_id')
                    ->numeric(),
                TextInput::make('code'),
                TextInput::make('name')
                    ->required(),
                TextInput::make('serial_no'),
                TextInput::make('location'),
                DatePicker::make('purchase_date'),
                TextInput::make('value')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                DatePicker::make('warranty_until'),
                TextInput::make('status')
                    ->required()
                    ->default('active'),
            ]);
    }
}
