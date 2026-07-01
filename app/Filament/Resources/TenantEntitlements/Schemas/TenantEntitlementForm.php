<?php

namespace App\Filament\Resources\TenantEntitlements\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TenantEntitlementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                Select::make('feature_id')
                    ->relationship('feature', 'name')
                    ->required(),
                Toggle::make('enabled')
                    ->required(),
                TextInput::make('source')
                    ->required()
                    ->default('plan'),
                DateTimePicker::make('starts_at'),
                DateTimePicker::make('ends_at'),
                TextInput::make('limits_json'),
            ]);
    }
}
