<?php

namespace App\Filament\Resources\Tenants\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TenantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Mã')->searchable(),
                TextColumn::make('name')->label('Tên đơn vị')->searchable(),
                TextColumn::make('tax_code')->label('MST')->toggleable(),
                TextColumn::make('phone')->label('Điện thoại')->toggleable(),
                TextColumn::make('plan')
                    ->label('Gói')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'trial' => 'Dùng thử', 'standard' => 'Tiêu chuẩn', 'enterprise' => 'Doanh nghiệp', default => $state,
                    }),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn (?string $state) => $state === 'active' ? 'success' : 'gray'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
