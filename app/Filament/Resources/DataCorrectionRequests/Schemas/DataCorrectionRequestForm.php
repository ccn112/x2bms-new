<?php

namespace App\Filament\Resources\DataCorrectionRequests\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Schema;

class DataCorrectionRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required(),
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->default(null),
                TextInput::make('support_ticket_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('data_type')
                    ->required(),
                TextInput::make('target_entity')
                    ->default(null),
                TextInput::make('affected_records')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('risk')
                    ->required()
                    ->default('medium'),
                TextInput::make('status')
                    ->required()
                    ->default('draft'),
                RichEditor::make('reason')
                    ->default(null)
                    ->columnSpanFull(),
                RichEditor::make('rollback_plan')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('requested_by')
                    ->numeric()
                    ->default(null),
                Select::make('approver_id')
                    ->relationship('approver', 'name')
                    ->default(null),
                DateTimePicker::make('approved_at'),
                DateTimePicker::make('execution_window_at'),
            ]);
    }
}
