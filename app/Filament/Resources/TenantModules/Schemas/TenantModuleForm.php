<?php

namespace App\Filament\Resources\TenantModules\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TenantModuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                TextInput::make('module_key')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                Toggle::make('enabled')
                    ->required(),
                TextInput::make('config'),
            ]);
    }
}
