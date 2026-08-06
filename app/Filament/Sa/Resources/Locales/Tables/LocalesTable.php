<?php

namespace App\Filament\Sa\Resources\Locales\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class LocalesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('code')->label('Mã')->searchable()->weight('medium'),
                TextColumn::make('name')->label('Tên')->searchable(),
                TextColumn::make('native_name')->label('Tên bản địa'),
                ToggleColumn::make('enabled')->label('Bật'),
                IconColumn::make('is_default')->label('Mặc định')->boolean()->alignCenter(),
                TextColumn::make('sort_order')->label('Thứ tự')->alignCenter()->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->emptyStateHeading('Chưa có ngôn ngữ')
            ->striped();
    }
}
