<?php

namespace App\Filament\Resources\PaymentRequests\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PaymentRequestForm
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
                Select::make('fund_id')
                    ->relationship('fund', 'name'),
                Select::make('approval_request_id')
                    ->relationship('approvalRequest', 'title'),
                TextInput::make('code'),
                TextInput::make('title')
                    ->required(),
                TextInput::make('payee'),
                TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('category'),
                TextInput::make('status')
                    ->required()
                    ->default('draft'),
                DatePicker::make('due_date'),
                DateTimePicker::make('paid_at'),
                TextInput::make('requested_by_id')
                    ->numeric(),
            ]);
    }
}
