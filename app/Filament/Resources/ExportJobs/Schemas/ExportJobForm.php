<?php

namespace App\Filament\Resources\ExportJobs\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ExportJobForm
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
                TextInput::make('format')
                    ->required()
                    ->default('xlsx'),
                TextInput::make('status')
                    ->required()
                    ->default('queued'),
                TextInput::make('file_path'),
                TextInput::make('params'),
                TextInput::make('created_by_id')
                    ->numeric(),
                DateTimePicker::make('finished_at'),
            ]);
    }
}
