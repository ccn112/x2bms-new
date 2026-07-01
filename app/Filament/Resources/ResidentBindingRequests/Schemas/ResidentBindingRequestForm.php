<?php

namespace App\Filament\Resources\ResidentBindingRequests\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ResidentBindingRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code'),
                TextInput::make('user_account_id')
                    ->required()
                    ->numeric(),
                Select::make('tenant_id')
                    ->relationship('tenant', 'name'),
                TextInput::make('project_id')
                    ->numeric(),
                TextInput::make('building_id')
                    ->numeric(),
                Select::make('apartment_id')
                    ->relationship('apartment', 'id'),
                TextInput::make('requested_role')
                    ->required()
                    ->default('owner'),
                TextInput::make('evidence_files_json'),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
                DateTimePicker::make('requested_at'),
                TextInput::make('reviewed_by')
                    ->numeric(),
                DateTimePicker::make('reviewed_at'),
                TextInput::make('review_note'),
            ]);
    }
}
