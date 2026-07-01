<?php

namespace App\Filament\Resources\TenantPartnerAssignments\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TenantPartnerAssignmentForm
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
                TextInput::make('building_id')
                    ->numeric(),
                Select::make('partner_id')
                    ->relationship('partner', 'name')
                    ->required(),
                TextInput::make('assignment_type')
                    ->required()
                    ->default('approved_vendor'),
                TextInput::make('contract_no'),
                DatePicker::make('start_date'),
                DatePicker::make('end_date'),
                TextInput::make('note'),
            ]);
    }
}
