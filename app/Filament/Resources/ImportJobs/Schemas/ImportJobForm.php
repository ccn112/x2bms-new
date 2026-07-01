<?php

namespace App\Filament\Resources\ImportJobs\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ImportJobForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                TextInput::make('type')
                    ->required(),
                TextInput::make('file_path'),
                TextInput::make('status')
                    ->required()
                    ->default('queued'),
                TextInput::make('total_rows')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('success_rows')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('error_rows')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('created_by_id')
                    ->numeric(),
                DateTimePicker::make('finished_at'),
            ]);
    }
}
