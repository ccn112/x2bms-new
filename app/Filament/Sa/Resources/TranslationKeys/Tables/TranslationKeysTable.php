<?php

namespace App\Filament\Sa\Resources\TranslationKeys\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class TranslationKeysTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Current published product-scope values as scalar subqueries (no N+1).
            ->modifyQueryUsing(fn ($query) => $query
                ->with('namespace')
                ->addSelect([
                    'current_vi' => self::valueSubquery('vi-VN'),
                    'current_en' => self::valueSubquery('en-US'),
                ]))
            ->defaultSort('key')
            ->columns([
                TextColumn::make('namespace.code')->label('Namespace')->badge()->color('gray')->sortable(),
                TextColumn::make('key')->label('Khóa')->searchable()->weight('medium')->wrap()
                    ->description(fn ($record) => $record->description),
                TextColumn::make('current_vi')->label('vi-VN')->wrap()->limit(60)
                    ->placeholder('— chưa có —'),
                TextColumn::make('current_en')->label('en-US')->wrap()->limit(60)
                    ->placeholder('— chưa có —'),
                IconColumn::make('is_critical')->label('Khóa hệ thống')->boolean()->alignCenter(),
                IconColumn::make('allow_tenant_override')->label('Cho tenant sửa')->boolean()->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('namespace_id')
                    ->label('Namespace')
                    ->relationship('namespace', 'code'),
                TernaryFilter::make('is_critical')->label('Khóa hệ thống'),
            ])
            ->recordActions([
                EditAction::make()->label('Sửa bản dịch'),
            ])
            ->emptyStateHeading('Chưa có khóa dịch')
            ->striped()
            ->paginated([25, 50, 100]);
    }

    private static function valueSubquery(string $locale): \Illuminate\Database\Query\Builder
    {
        return DB::table('translation_values')
            ->select('value')
            ->whereColumn('translation_values.translation_key_id', 'translation_keys.id')
            ->where('locale', $locale)
            ->where('scope_type', 'product')
            ->where('scope_id', '')
            ->where('status', 'published')
            ->limit(1);
    }
}
