<?php

namespace App\Filament\Resources\WebhookDeliveryAttempts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class WebhookDeliveryAttemptForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('webhook_endpoint_id')
                    ->required()
                    ->numeric(),
                TextInput::make('event_id')
                    ->default(null),
                TextInput::make('correlation_id')
                    ->default(null),
                TextInput::make('payload_hash')
                    ->default(null),
                TextInput::make('http_status')
                    ->numeric()
                    ->default(null),
                TextInput::make('duration_ms')
                    ->numeric()
                    ->default(null),
                TextInput::make('status')
                    ->required(),
                TextInput::make('attempt_no')
                    ->required()
                    ->numeric()
                    ->default(1),
                Textarea::make('response_body')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('error_message')
                    ->default(null)
                    ->columnSpanFull(),
                DateTimePicker::make('delivered_at'),
            ]);
    }
}
