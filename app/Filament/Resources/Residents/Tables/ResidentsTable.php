<?php

namespace App\Filament\Resources\Residents\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ResidentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar_url')
                    ->label('')
                    ->circular()
                    ->size(40),
                TextColumn::make('code')->label('Mã CD')->searchable(),
                TextColumn::make('full_name')->label('Họ tên')->searchable(),
                TextColumn::make('phone')->label('Điện thoại')->searchable(),
                TextColumn::make('email')->label('Email')->searchable()->toggleable(),
                TextColumn::make('id_no')->label('CCCD')->searchable()->toggleable(),
                TextColumn::make('profile_status')
                    ->label('Hồ sơ')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'cho_bo_sung' => 'Chờ bổ sung', 'cho_duyet' => 'Chờ duyệt', 'hoat_dong' => 'Hoạt động', 'tu_choi' => 'Từ chối', default => $state,
                    })
                    ->color(fn (?string $state) => match ($state) {
                        'hoat_dong' => 'success', 'cho_duyet' => 'warning', 'tu_choi' => 'danger', default => 'gray',
                    }),
                TextColumn::make('status')
                    ->label('Tài khoản')
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
