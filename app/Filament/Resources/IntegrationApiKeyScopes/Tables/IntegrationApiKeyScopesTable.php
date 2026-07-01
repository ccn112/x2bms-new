<?php

namespace App\Filament\Resources\IntegrationApiKeyScopes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class IntegrationApiKeyScopesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('apiKey.name')
                    ->searchable(),
                TextColumn::make('scope_code')
                    ->searchable(),
                TextColumn::make('scope_name')
                    ->searchable(),
                TextColumn::make('permission_level')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
