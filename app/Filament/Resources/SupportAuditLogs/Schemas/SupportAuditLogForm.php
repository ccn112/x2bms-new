<?php

namespace App\Filament\Resources\SupportAuditLogs\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Schema;

class SupportAuditLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('actor_id')
                    ->relationship('actor', 'name')
                    ->default(null),
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->default(null),
                TextInput::make('entity_type')
                    ->required(),
                TextInput::make('entity_id')
                    ->default(null),
                TextInput::make('action')
                    ->required(),
                RichEditor::make('before_json')
                    ->default(null)
                    ->columnSpanFull(),
                RichEditor::make('after_json')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('reason')
                    ->default(null),
                TextInput::make('ip_address')
                    ->default(null),
                TextInput::make('user_agent')
                    ->default(null),
            ]);
    }
}
