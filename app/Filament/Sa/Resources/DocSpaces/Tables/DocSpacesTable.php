<?php

namespace App\Filament\Sa\Resources\DocSpaces\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DocSpacesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('title')
                    ->label('Tiêu đề')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('key')
                    ->label('Khóa')
                    ->badge()
                    ->searchable(),
                TextColumn::make('audience')
                    ->label('Đối tượng')
                    ->badge(),
                TextColumn::make('pages_count')
                    ->label('Số trang')
                    ->counts('pages'),
                IconColumn::make('is_published')
                    ->label('Xuất bản')
                    ->boolean(),
                IconColumn::make('is_public')
                    ->label('Công khai')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label('Cập nhật')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('audience')
                    ->label('Đối tượng')
                    ->options([
                        'dev' => 'dev', 'ops' => 'ops', 'bql' => 'bql',
                        'hq' => 'hq', 'sa' => 'sa', 'resident' => 'resident',
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
