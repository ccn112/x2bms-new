<?php

namespace App\Filament\Resources\WorkOrders\Schemas;

use App\Enums\WorkOrderStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class WorkOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                Select::make('building_id')
                    ->relationship('building', 'name')
                    ->required(),
                Select::make('project_id')
                    ->relationship('project', 'name'),
                Select::make('apartment_id')
                    ->relationship('apartment', 'id'),
                Select::make('department_id')
                    ->relationship('department', 'name'),
                TextInput::make('assigned_to_id')
                    ->numeric(),
                Select::make('team_id')
                    ->relationship('team', 'name'),
                TextInput::make('created_by_id')
                    ->numeric(),
                TextInput::make('feedback_request_id')
                    ->numeric(),
                TextInput::make('code')
                    ->required(),
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('category'),
                Select::make('status')
                    ->options(WorkOrderStatus::class)
                    ->default('pending')
                    ->required(),
                TextInput::make('priority')
                    ->required()
                    ->default('normal'),
                DateTimePicker::make('due_at'),
                DateTimePicker::make('scheduled_at'),
                DateTimePicker::make('started_at'),
                DateTimePicker::make('completed_at'),
                TextInput::make('cost')
                    ->required()
                    ->numeric()
                    ->default(0.0)
                    ->prefix('$'),
            ]);
    }
}
