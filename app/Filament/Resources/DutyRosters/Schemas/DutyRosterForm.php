<?php

namespace App\Filament\Resources\DutyRosters\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DutyRosterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('shift_id')
                    ->relationship('shift', 'name')
                    ->required(),
                Select::make('user_id')
                    ->relationship('user', 'name'),
                DatePicker::make('duty_date')
                    ->required(),
                TextInput::make('status')
                    ->required()
                    ->default('scheduled'),
                TextInput::make('note'),
            ]);
    }
}
