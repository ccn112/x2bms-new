<?php

namespace App\Filament\Resources\DataFixRequests\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class DataFixRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                TextInput::make('code'),
                TextInput::make('entity')
                    ->required(),
                TextInput::make('target_id')
                    ->numeric(),
                Textarea::make('reason')
                    ->columnSpanFull(),
                TextInput::make('requested_change'),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
                TextInput::make('requested_by_id')
                    ->numeric(),
                TextInput::make('approved_by_id')
                    ->numeric(),
                DateTimePicker::make('applied_at'),
            ]);
    }
}
