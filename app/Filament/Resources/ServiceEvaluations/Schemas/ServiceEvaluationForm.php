<?php

namespace App\Filament\Resources\ServiceEvaluations\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ServiceEvaluationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                Select::make('feedback_request_id')
                    ->relationship('feedbackRequest', 'title'),
                Select::make('work_order_id')
                    ->relationship('workOrder', 'title'),
                Select::make('resident_id')
                    ->relationship('resident', 'id'),
                TextInput::make('rating')
                    ->required()
                    ->numeric()
                    ->default(5),
                TextInput::make('criteria'),
                TextInput::make('comment'),
                DateTimePicker::make('evaluated_at'),
            ]);
    }
}
