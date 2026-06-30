<?php

namespace App\Filament\Resources\Projects\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Mã')->searchable(),
                TextColumn::make('name')->label('Dự án')->searchable(),
                TextColumn::make('tenant.name')->label('Đơn vị QL')->toggleable(),
                TextColumn::make('type')
                    ->label('Loại')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'apartment' => 'Chung cư', 'urban_area' => 'Khu đô thị', 'complex' => 'Phức hợp', 'office' => 'Văn phòng', default => $state,
                    }),
                TextColumn::make('building_count')->label('Số tòa')->numeric()->sortable(),
                TextColumn::make('apartment_count')->label('Số căn')->numeric()->sortable(),
                TextColumn::make('investor')->label('Chủ đầu tư')->toggleable(),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn (?string $state) => $state === 'active' ? 'success' : 'gray'),
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
