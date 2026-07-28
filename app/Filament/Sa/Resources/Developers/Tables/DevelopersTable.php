<?php

namespace App\Filament\Sa\Resources\Developers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DevelopersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('publicProjects'))
            ->defaultSort('public_projects_count', 'desc')
            ->columns([
                ImageColumn::make('logo_path')->label('Logo')->disk('public')->circular()->height(32),
                TextColumn::make('name')->label('Chủ đầu tư')->searchable()->weight('medium')
                    ->description(fn ($record) => $record->website),
                TextColumn::make('public_projects_count')->label('Số dự án')->badge()->color('info')->alignCenter()->sortable(),
                TextColumn::make('slug')->label('Slug')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label('Tạo lúc')->dateTime('d/m/Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Chưa có chủ đầu tư')
            ->striped();
    }
}
