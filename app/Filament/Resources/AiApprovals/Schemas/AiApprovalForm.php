<?php

namespace App\Filament\Resources\AiApprovals\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AiApprovalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name'),
                TextInput::make('ai_usage_log_id')
                    ->numeric(),
                TextInput::make('action'),
                TextInput::make('risk_level')
                    ->required()
                    ->default('high'),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
                TextInput::make('requested_by_id')
                    ->numeric(),
                Select::make('approver_id')
                    ->relationship('approver', 'name'),
                TextInput::make('note'),
                DateTimePicker::make('decided_at'),
            ]);
    }
}
