<?php

namespace App\Filament\Resources\SharedPartners\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SharedPartnerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('partner_type')
                    ->required()
                    ->default('contractor'),
                Select::make('category_id')
                    ->relationship('category', 'name'),
                TextInput::make('tax_code'),
                TextInput::make('legal_name'),
                TextInput::make('contact_name'),
                TextInput::make('phone')
                    ->tel(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                TextInput::make('website')
                    ->url(),
                TextInput::make('address'),
                TextInput::make('service_area'),
                TextInput::make('verification_status')
                    ->required()
                    ->default('unverified'),
                TextInput::make('rating_avg')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('kpi_score')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('metadata_json'),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
