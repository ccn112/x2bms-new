<?php

namespace App\Filament\Resources\SupportTeams\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SupportTeamForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('level')
                    ->required()
                    ->default('L1'),
                TextInput::make('sla_target_response_minutes')
                    ->required()
                    ->numeric()
                    ->default(60),
                TextInput::make('member_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
