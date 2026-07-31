<?php

namespace App\Filament\Resources\Payments\Tables;

use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * CHỈ ĐỌC (2026-07-31, gate G10 money & authority) — `payments` mang bất biến
 * tiền, không được có Resource sửa/xoá được (`docs/delivery/02_FILAMENT_DECISION_MATRIX.md`).
 * Đã bỏ `EditAction`/`RestoreAction`/`ForceDeleteAction` + toàn bộ bulk action
 * (từng cho sửa tự do cột `status`, xoá cứng, khôi phục ngoài mọi review) —
 * đây chính là "đường vòng /fila/payments" mà gói AI-First Delivery cảnh báo.
 * Duyệt chứng từ cư dân đi qua `Pages/PaymentClaimQueue.php` (dùng
 * `ResidentPaymentClaimReviewer`, có transaction + lock + idempotent).
 */
class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tenant.name')
                    ->searchable(),
                TextColumn::make('building_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('apartment.id')
                    ->searchable(),
                TextColumn::make('resident.id')
                    ->searchable(),
                TextColumn::make('payment_method_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('amount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('paid_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('reference_no')
                    ->searchable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('note')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ]);
    }
}
