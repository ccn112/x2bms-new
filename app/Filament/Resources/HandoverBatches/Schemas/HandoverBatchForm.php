<?php

namespace App\Filament\Resources\HandoverBatches\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class HandoverBatchForm
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
                TextInput::make('building_id')
                    ->numeric(),
                TextInput::make('code'),
                TextInput::make('name')
                    ->required(),
                DatePicker::make('scheduled_date'),
                TextInput::make('total_units')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('status')
                    ->required()
                    ->default('planned'),
            ]);
    }
}
