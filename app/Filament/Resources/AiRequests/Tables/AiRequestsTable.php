<?php

namespace App\Filament\Resources\AiRequests\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AiRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tenant.name')
                    ->searchable(),
                TextColumn::make('user.name')
                    ->searchable(),
                TextColumn::make('ai_chat_session_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('mode')
                    ->searchable(),
                TextColumn::make('model')
                    ->searchable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('tokens_in')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('tokens_out')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('latency_ms')
                    ->numeric()
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
