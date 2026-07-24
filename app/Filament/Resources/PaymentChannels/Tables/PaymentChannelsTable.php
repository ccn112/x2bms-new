<?php

namespace App\Filament\Resources\PaymentChannels\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class PaymentChannelsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('tenant.name')
                    ->label('Đơn vị')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('project.name')
                    ->label('Dự án')
                    ->placeholder('— Tất cả dự án')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('channel')
                    ->label('Cổng')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'vietqr' => 'VietQR',
                        'vnpay' => 'VNPay',
                        'momo' => 'MoMo',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'vietqr' => 'success',
                        'vnpay' => 'info',
                        'momo' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('display_name')
                    ->label('Tên hiển thị')
                    ->placeholder('—')
                    ->searchable(),
                ToggleColumn::make('is_enabled')
                    ->label('Bật'),
                TextColumn::make('sort')
                    ->label('Thứ tự')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Tạo lúc')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('channel')
                    ->label('Cổng')
                    ->options([
                        'vietqr' => 'VietQR',
                        'vnpay' => 'VNPay',
                        'momo' => 'MoMo',
                    ]),
                TernaryFilter::make('is_enabled')
                    ->label('Trạng thái bật'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }
}
