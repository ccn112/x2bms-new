<?php

namespace App\Filament\Resources\RealEstateListings\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RealEstateListingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                TextInput::make('project_id')
                    ->numeric(),
                Select::make('apartment_id')
                    ->relationship('apartment', 'id'),
                TextInput::make('owner_resident_id')
                    ->numeric(),
                TextInput::make('code'),
                TextInput::make('type')
                    ->required()
                    ->default('sale'),
                TextInput::make('title')
                    ->required(),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->default(0.0)
                    ->prefix('$'),
                TextInput::make('area')
                    ->numeric(),
                TextInput::make('bedrooms')
                    ->numeric(),
                TextInput::make('status')
                    ->required()
                    ->default('active'),
                DateTimePicker::make('published_at'),
            ]);
    }
}
