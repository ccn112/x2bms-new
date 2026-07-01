<?php

namespace App\Filament\Resources\WarrantyRequests\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class WarrantyRequestForm
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
                Select::make('resident_id')
                    ->relationship('resident', 'id'),
                TextInput::make('code'),
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('category'),
                TextInput::make('status')
                    ->required()
                    ->default('open'),
                DateTimePicker::make('reported_at'),
                DateTimePicker::make('resolved_at'),
            ]);
    }
}
