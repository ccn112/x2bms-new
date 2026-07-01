<?php

namespace App\Filament\Resources\AccessLogs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AccessLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tenant.name')
                    ->searchable(),
                TextColumn::make('project_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('building.name')
                    ->searchable(),
                TextColumn::make('apartment.id')
                    ->searchable(),
                TextColumn::make('resident.id')
                    ->searchable(),
                TextColumn::make('visitor_pass_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('accessCard.id')
                    ->searchable(),
                TextColumn::make('device_name')
                    ->searchable(),
                TextColumn::make('gate')
                    ->searchable(),
                TextColumn::make('direction')
                    ->searchable(),
                TextColumn::make('method')
                    ->searchable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('event_at')
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
