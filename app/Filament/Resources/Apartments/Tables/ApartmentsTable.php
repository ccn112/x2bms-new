<?php

namespace App\Filament\Resources\Apartments\Tables;

use App\Filament\Concerns\SafeDeleteActions;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ApartmentsTable
{
    use SafeDeleteActions;

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Mã căn')->searchable(),
                TextColumn::make('building.name')->label('Tòa')->sortable(),
                TextColumn::make('floor.name')->label('Tầng')->toggleable(),
                TextColumn::make('type')->label('Loại căn')->toggleable(),
                TextColumn::make('area_sqm')->label('DT (m²)')->numeric()->sortable(),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'occupied' => 'Đang ở', 'vacant' => 'Trống', 'handover' => 'Chờ bàn giao', 'locked' => 'Tạm khóa', default => $state,
                    })
                    ->color(fn (?string $state) => match ($state) {
                        'occupied' => 'success', 'vacant' => 'gray', 'handover' => 'warning', 'locked' => 'danger', default => 'gray',
                    }),
            ])
            ->filters([
                TrashedFilter::make(),
                //
            ])
            ->recordActions([
                RestoreAction::make(),
                // Cascade quan hệ căn hộ do ApartmentObserver xử lý (mọi code path).
                self::safeSoftDelete('căn hộ', blockers: [
                    'statements' => 'bảng kê',
                    'debts' => 'khoản công nợ',
                ]),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    RestoreBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
