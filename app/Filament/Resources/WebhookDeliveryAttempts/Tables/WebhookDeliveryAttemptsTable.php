<?php

namespace App\Filament\Resources\WebhookDeliveryAttempts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WebhookDeliveryAttemptsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('webhook_endpoint_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('event_id')
                    ->searchable(),
                TextColumn::make('correlation_id')
                    ->searchable(),
                TextColumn::make('payload_hash')
                    ->searchable(),
                TextColumn::make('http_status')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('duration_ms')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('attempt_no')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('delivered_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
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
