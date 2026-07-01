<?php

namespace App\Filament\Resources\SupportSlaPolicies\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SupportSlaPolicyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('priority')
                    ->required()
                    ->default('medium'),
                TextInput::make('response_minutes')
                    ->required()
                    ->numeric()
                    ->default(60),
                TextInput::make('resolution_minutes')
                    ->required()
                    ->numeric()
                    ->default(480),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
