<?php

namespace App\Filament\Sa\Resources\TranslationKeys\Tables;

use App\Services\Localization\TranslationKeyKind;
use App\Services\Localization\TranslationValueWriter;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
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
            ->groups([
                Group::make('kind')
                    ->label('Loại')
                    ->getTitleFromRecordUsing(fn ($record) => TranslationKeyKind::meta($record->kind)['label']),
                Group::make('namespace.code')->label('Namespace'),
                Group::make('category')->label('Nhóm'),
            ])
            ->columns([
                TextColumn::make('namespace.code')->label('Namespace')->badge()->color('gray')->sortable()
                    ->toggleable(),
                TextColumn::make('kind')
                    ->label('Loại')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => TranslationKeyKind::meta($state)['label'])
                    ->color(fn (?string $state) => TranslationKeyKind::meta($state)['color'])
                    ->sortable(),
                TextColumn::make('category')->label('Nhóm')->badge()->color('gray')->sortable()
                    ->toggleable(),
                TextColumn::make('key')->label('Khóa')->searchable()->weight('medium')->wrap()
                    ->description(fn ($record) => $record->description)
                    ->copyable(),
                TextInputColumn::make('current_vi')
                    ->label('vi-VN')
                    ->placeholder('— chưa có —')
                    ->disabled(fn ($record) => (bool) $record->is_critical)
                    ->updateStateUsing(fn ($record, $state) => self::writeValue($record, 'vi-VN', $state)),
                TextInputColumn::make('current_en')
                    ->label('en-US')
                    ->placeholder('— chưa có —')
                    ->disabled(fn ($record) => (bool) $record->is_critical)
                    ->updateStateUsing(fn ($record, $state) => self::writeValue($record, 'en-US', $state)),
                IconColumn::make('is_critical')->label('Hệ thống')->boolean()->alignCenter()
                    ->toggleable(),
                IconColumn::make('allow_tenant_override')->label('Tenant sửa')->boolean()->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('namespace_id')
                    ->label('Namespace')
                    ->relationship('namespace', 'code'),
                SelectFilter::make('kind')
                    ->label('Loại')
                    ->options(TranslationKeyKind::options()),
                SelectFilter::make('category')
                    ->label('Nhóm')
                    ->options(fn () => DB::table('translation_keys')
                        ->whereNotNull('category')
                        ->distinct()
                        ->orderBy('category')
                        ->pluck('category', 'category')
                        ->all()),
                TernaryFilter::make('is_critical')->label('Khóa hệ thống'),
            ])
            ->recordActions([
                EditAction::make()->label('Chi tiết'),
            ])
            ->emptyStateHeading('Chưa có khóa dịch')
            ->striped()
            ->paginated([25, 50, 100]);
    }

    /**
     * Inline-edit writer: persists the product-scope value and keeps the cell showing the
     * saved text. Critical keys are disabled in the UI, so they never reach here.
     */
    private static function writeValue($record, string $locale, ?string $state): ?string
    {
        if ((bool) $record->is_critical) {
            return $state;
        }

        app(TranslationValueWriter::class)->writeProductValue((int) $record->id, $locale, $state);

        return $state === null ? null : trim($state);
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
