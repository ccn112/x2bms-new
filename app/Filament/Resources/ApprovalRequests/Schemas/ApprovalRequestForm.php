<?php

namespace App\Filament\Resources\ApprovalRequests\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ApprovalRequestForm
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
                TextInput::make('building_id')
                    ->numeric(),
                TextInput::make('code'),
                TextInput::make('type')
                    ->required()
                    ->default('expense'),
                TextInput::make('subject_type'),
                TextInput::make('subject_id')
                    ->numeric(),
                TextInput::make('title')
                    ->required(),
                TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
                TextInput::make('current_step')
                    ->required()
                    ->numeric()
                    ->default(1),
                TextInput::make('requested_by_id')
                    ->numeric(),
                TextInput::make('note'),
                DateTimePicker::make('decided_at'),
            ]);
    }
}
