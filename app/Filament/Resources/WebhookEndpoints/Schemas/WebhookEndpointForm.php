<?php

namespace App\Filament\Resources\WebhookEndpoints\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class WebhookEndpointForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required(),
                TextInput::make('endpoint_name')
                    ->required(),
                TextInput::make('url')
                    ->url()
                    ->required(),
                Select::make('event_group_id')
                    ->relationship('eventGroup', 'name')
                    ->default(null),
                TextInput::make('method')
                    ->required()
                    ->default('POST'),
                TextInput::make('signature_type')
                    ->required()
                    ->default('HMAC'),
                TextInput::make('status')
                    ->required()
                    ->default('pending_verification'),
                TextInput::make('success_rate')
                    ->numeric()
                    ->default(null),
                TextInput::make('retry_policy')
                    ->default(null),
                TextInput::make('owner_name')
                    ->default(null),
                DateTimePicker::make('last_delivery_at'),
                Textarea::make('metadata_json')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}
