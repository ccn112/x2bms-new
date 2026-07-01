<?php

namespace App\Filament\Resources\AiRetrievalLogs\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class AiRetrievalLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name'),
                TextInput::make('tenant_id')
                    ->numeric(),
                TextInput::make('project_id')
                    ->numeric(),
                TextInput::make('building_id')
                    ->numeric(),
                Textarea::make('question')
                    ->columnSpanFull(),
                Textarea::make('answer_summary')
                    ->columnSpanFull(),
                TextInput::make('retrieved_document_ids_json'),
                TextInput::make('blocked_document_ids_json'),
                TextInput::make('permission_snapshot_json'),
                TextInput::make('model'),
                TextInput::make('token_input')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('token_output')
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
