<?php

namespace App\Filament\Resources\SaasPlans\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SaasPlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('description'),
                TextInput::make('price_monthly')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('price_yearly')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('max_projects')
                    ->numeric(),
                TextInput::make('max_units')
                    ->numeric(),
                TextInput::make('status')
                    ->required()
                    ->default('active'),
            ]);
    }
}
