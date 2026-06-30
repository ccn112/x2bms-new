<?php

namespace App\Filament\Resources\StaffProfiles\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StaffProfilesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee_code')->label('Mã NV')->searchable(),
                TextColumn::make('user.name')->label('Họ tên')->searchable(),
                TextColumn::make('position')->label('Chức danh')->searchable(),
                TextColumn::make('department.name')->label('Phòng ban')->sortable(),
                TextColumn::make('phone')->label('Điện thoại'),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'suspended' => 'warning',
                        'left' => 'gray',
                        default => 'gray',
                    }),
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
