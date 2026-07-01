<?php

namespace App\Filament\Resources\AiRequests\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class AiRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name'),
                Select::make('user_id')
                    ->relationship('user', 'name'),
                TextInput::make('ai_chat_session_id')
                    ->numeric(),
                TextInput::make('mode')
                    ->required()
                    ->default('context'),
                TextInput::make('model'),
                Textarea::make('prompt')
                    ->columnSpanFull(),
                TextInput::make('status')
                    ->required()
                    ->default('success'),
                TextInput::make('tokens_in')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('tokens_out')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('latency_ms')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
