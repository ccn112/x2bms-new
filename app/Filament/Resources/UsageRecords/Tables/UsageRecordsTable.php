<?php

namespace App\Filament\Resources\UsageRecords\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsageRecordsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('usage_period_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('tenant.name')
                    ->searchable(),
                TextColumn::make('meter_type')
                    ->searchable(),
                TextColumn::make('usage_value')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('included_limit')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('overage_value')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('overage_amount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('source')
                    ->searchable(),
                TextColumn::make('status')
                    ->searchable(),
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
