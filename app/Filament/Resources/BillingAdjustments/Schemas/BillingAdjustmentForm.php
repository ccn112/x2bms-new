<?php

namespace App\Filament\Resources\BillingAdjustments\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BillingAdjustmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('case_id')
                    ->required(),
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                Select::make('invoice_id')
                    ->relationship('invoice', 'id'),
                TextInput::make('adjustment_type')
                    ->required()
                    ->default('usage_adjustment'),
                TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('reason'),
                TextInput::make('evidence_file_url')
                    ->url(),
                TextInput::make('status')
                    ->required()
                    ->default('pending_approval'),
                TextInput::make('requested_by')
                    ->numeric(),
                TextInput::make('approved_by')
                    ->numeric(),
                DateTimePicker::make('approved_at'),
                TextInput::make('metadata_json'),
            ]);
    }
}
