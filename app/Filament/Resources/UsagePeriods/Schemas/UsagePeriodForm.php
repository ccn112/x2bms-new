<?php

namespace App\Filament\Resources\UsagePeriods\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UsagePeriodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required(),
                DatePicker::make('period_start'),
                DatePicker::make('period_end'),
                TextInput::make('status')
                    ->required()
                    ->default('open'),
                DateTimePicker::make('locked_at'),
                TextInput::make('locked_by'),
            ]);
    }
}
