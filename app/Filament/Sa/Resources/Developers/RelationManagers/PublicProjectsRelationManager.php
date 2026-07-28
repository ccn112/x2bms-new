<?php

namespace App\Filament\Sa\Resources\Developers\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Danh sách dự án của một chủ đầu tư. */
class PublicProjectsRelationManager extends RelationManager
{
    protected static string $relationship = 'publicProjects';

    protected static ?string $title = 'Dự án của chủ đầu tư';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('code')->label('Mã')->searchable(),
                TextColumn::make('name')->label('Dự án')->searchable()->weight('medium'),
                TextColumn::make('district')->label('Quận/Huyện')->placeholder('—'),
                TextColumn::make('province')->label('Tỉnh/TP')->placeholder('—'),
                TextColumn::make('apartments')->label('Căn hộ')->numeric()->alignCenter(),
            ])
            ->paginated([10, 25, 50]);
    }
}
