<?php

namespace App\Filament\Resources\IntegrationRetryJobs\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class IntegrationRetryJobForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('event_id')
                    ->default(null),
                TextInput::make('webhook_endpoint_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('source')
                    ->default(null),
                TextInput::make('reason')
                    ->default(null),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
                TextInput::make('attempt_no')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('max_attempts')
                    ->required()
                    ->numeric()
                    ->default(5),
                DateTimePicker::make('next_retry_at'),
                Textarea::make('last_error')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}
