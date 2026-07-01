<?php

namespace App\Filament\Resources\TenantSupportProfiles\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Schema;

class TenantSupportProfileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                TextInput::make('support_plan')
                    ->default(null),
                TextInput::make('tier')
                    ->default(null),
                TextInput::make('health_score')
                    ->numeric()
                    ->default(null),
                TextInput::make('csat')
                    ->numeric()
                    ->default(null),
                Select::make('account_manager_id')
                    ->relationship('accountManager', 'name')
                    ->default(null),
                RichEditor::make('vip_notes')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}
