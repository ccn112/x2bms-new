<?php

namespace App\Filament\Resources\GlobalUserAccounts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class GlobalUserAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('uuid')
                    ->label('UUID')
                    ->required(),
                TextInput::make('phone')
                    ->tel(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                TextInput::make('full_name'),
                TextInput::make('avatar_url')
                    ->url(),
                TextInput::make('identity_status')
                    ->required()
                    ->default('unverified'),
                TextInput::make('account_status')
                    ->required()
                    ->default('active'),
                TextInput::make('account_type')
                    ->required()
                    ->default('public_user'),
                DateTimePicker::make('first_registered_at'),
                DateTimePicker::make('last_login_at'),
                TextInput::make('risk_score')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('duplicate_group_id'),
                TextInput::make('metadata_json'),
            ]);
    }
}
