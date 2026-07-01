<?php

namespace App\Filament\Resources\TenantProjectLinks\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TenantProjectLinkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                Select::make('project_id')
                    ->relationship('project', 'name'),
                Select::make('public_project_id')
                    ->relationship('publicProject', 'name'),
                TextInput::make('override_content_json'),
                TextInput::make('linked_by')
                    ->numeric(),
                DateTimePicker::make('linked_at'),
            ]);
    }
}
