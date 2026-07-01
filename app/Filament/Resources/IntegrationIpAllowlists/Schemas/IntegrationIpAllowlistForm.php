<?php

namespace App\Filament\Resources\IntegrationIpAllowlists\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class IntegrationIpAllowlistForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('scope_type')
                    ->required()
                    ->default('global'),
                TextInput::make('scope_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('ip_or_cidr')
                    ->required(),
                TextInput::make('description')
                    ->default(null),
                TextInput::make('created_by')
                    ->numeric()
                    ->default(null),
            ]);
    }
}
