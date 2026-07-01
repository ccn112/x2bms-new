<?php

namespace App\Filament\Resources\QuotaAlerts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class QuotaAlertsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('tenant.name')
                    ->searchable(),
                TextColumn::make('usage_period_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('meter_type')
                    ->searchable(),
                TextColumn::make('usage_value')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('included_limit')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('over_percent')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('estimated_fee')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('recommendation')
                    ->searchable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('owner_user_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('resolved_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
