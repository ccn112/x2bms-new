<?php

namespace App\Filament\Resources\FeeTypes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class FeeTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                TextInput::make('code')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('category')
                    ->required()
                    ->default('management'),
                TextInput::make('unit')
                    ->required()
                    ->default('per_sqm'),
                Toggle::make('is_recurring')
                    ->required(),
                TextInput::make('accounting_code'),
                TextInput::make('status')
                    ->required()
                    ->default('active'),
                TextInput::make('note'),
            ]);
    }
}
