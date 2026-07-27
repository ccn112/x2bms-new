<?php

namespace App\Filament\Sa\Resources\DocVersions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DocVersionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort', 'desc')
            ->columns([
                TextColumn::make('label')
                    ->label('Nhãn')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Tên đợt')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'released' => 'Đã phát hành',
                        'in_progress' => 'Đang làm',
                        default => 'Dự kiến',
                    })
                    ->color(fn (string $state) => match ($state) {
                        'released' => 'success',
                        'in_progress' => 'warning',
                        default => 'gray',
                    }),
                IconColumn::make('is_current')
                    ->label('Hiện hành')
                    ->boolean(),
                TextColumn::make('items_count')
                    ->label('Backlog')
                    ->counts('items')
                    ->badge(),
                TextColumn::make('pages_count')
                    ->label('Trang')
                    ->counts('pages'),
                TextColumn::make('released_at')
                    ->label('Phát hành')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'planned' => 'Dự kiến',
                        'in_progress' => 'Đang làm',
                        'released' => 'Đã phát hành',
                    ]),
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
