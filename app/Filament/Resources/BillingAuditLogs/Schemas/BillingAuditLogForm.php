<?php

namespace App\Filament\Resources\BillingAuditLogs\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BillingAuditLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('actor_id')
                    ->relationship('actor', 'name'),
                Select::make('tenant_id')
                    ->relationship('tenant', 'name'),
                TextInput::make('entity_type')
                    ->required(),
                TextInput::make('entity_id')
                    ->numeric(),
                TextInput::make('action')
                    ->required(),
                TextInput::make('before_json'),
                TextInput::make('after_json'),
                TextInput::make('reason'),
                TextInput::make('ip_address'),
            ]);
    }
}
