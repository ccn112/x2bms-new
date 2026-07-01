<?php

namespace App\Filament\Resources\SupportReports\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Schema;

class SupportReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->default(null),
                TextInput::make('period')
                    ->default(null),
                TextInput::make('type')
                    ->required()
                    ->default('resolution'),
                RichEditor::make('metrics_json')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('generated_by')
                    ->numeric()
                    ->default(null),
            ]);
    }
}
