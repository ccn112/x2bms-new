<?php

namespace App\Filament\Resources\Buildings\Tables;

use Filament\Actions\ForceDeleteBulkAction;

use Filament\Actions\RestoreBulkAction;

use Filament\Actions\ForceDeleteAction;

use Filament\Actions\RestoreAction;

use Filament\Tables\Filters\TrashedFilter;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BuildingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Mã')->searchable(),
                TextColumn::make('name')->label('Tòa')->searchable(),
                TextColumn::make('project.name')->label('Dự án')->sortable(),
                TextColumn::make('type')
                    ->label('Loại')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'residential' => 'Căn hộ', 'office' => 'Văn phòng', 'mixed' => 'Hỗn hợp', default => $state,
                    }),
                TextColumn::make('floor_count')->label('Số tầng')->numeric()->sortable(),
                TextColumn::make('apartment_count')->label('Số căn')->numeric()->sortable(),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn (?string $state) => $state === 'active' ? 'success' : 'gray'),
            ])
            ->filters([
                TrashedFilter::make(),
                //
            ])
            ->recordActions([
                RestoreAction::make(),
                ForceDeleteAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
