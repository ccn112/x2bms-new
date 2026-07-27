<?php

namespace App\Filament\Sa\Resources\DocPages\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class DocPagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('title')
                    ->label('Tiêu đề')
                    ->description(fn ($record) => $record->parent?->title ? '↳ trong: '.$record->parent->title : null)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('space.title')
                    ->label('Không gian')
                    ->badge()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->color('gray')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'published' ? 'Xuất bản' : 'Nháp')
                    ->color(fn (string $state) => $state === 'published' ? 'success' : 'gray'),
                TextColumn::make('revisions_count')
                    ->label('Version')
                    ->counts('revisions')
                    ->badge()
                    ->color('info'),
                TextColumn::make('editor.name')
                    ->label('Sửa bởi')
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label('Cập nhật')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('space_id')
                    ->label('Không gian')
                    ->relationship('space', 'title'),
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options(['draft' => 'Nháp', 'published' => 'Xuất bản']),
                TrashedFilter::make(),
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
