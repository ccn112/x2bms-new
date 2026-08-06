<?php

namespace App\Filament\Sa\Resources\TranslationReleases\Tables;

use App\Services\Localization\PublishTranslationRelease;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TranslationReleasesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('namespace'))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('namespace.code')->label('Namespace')->badge()->color('gray')->sortable(),
                TextColumn::make('locale')->label('Locale')->badge()->sortable(),
                TextColumn::make('version')->label('Phiên bản')->searchable()->weight('medium'),
                TextColumn::make('status')->label('Trạng thái')->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'published' => 'Đang phát hành',
                        'rolled_back' => 'Đã khôi phục',
                        'draft' => 'Nháp',
                        'archived' => 'Lưu trữ',
                        default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'published' => 'success',
                        'rolled_back' => 'warning',
                        'archived' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('checksum')->label('Checksum')
                    ->formatStateUsing(fn (?string $state) => $state ? substr($state, 0, 12).'…' : '—')
                    ->fontFamily('mono')->copyable()->copyableState(fn ($record) => $record->checksum),
                TextColumn::make('published_at')->label('Phát hành lúc')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('publisher.name')->label('Người phát hành')->placeholder('—')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('namespace_id')->label('Namespace')->relationship('namespace', 'code'),
                SelectFilter::make('locale')->label('Locale')->options(fn () => \App\Models\Locale::query()->pluck('code', 'code')->all()),
                SelectFilter::make('status')->label('Trạng thái')->options([
                    'published' => 'Đang phát hành',
                    'rolled_back' => 'Đã khôi phục',
                    'draft' => 'Nháp',
                    'archived' => 'Lưu trữ',
                ]),
            ])
            ->recordActions([
                Action::make('rollback')
                    ->label('Khôi phục')
                    ->icon('heroicon-m-arrow-uturn-left')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Khôi phục bản phát hành')
                    ->modalDescription('Đánh dấu gói này là "đã khôi phục" (không xóa) để bản phát hành trước đó trở lại hoạt động. Thao tác này được ghi nhật ký.')
                    ->visible(fn ($record) => $record->status === 'published')
                    ->action(function ($record): void {
                        app(PublishTranslationRelease::class)->rollback((int) $record->id);
                        Notification::make()->success()->title('Đã khôi phục gói')
                            ->body('Bản phát hành trước đó (nếu có) đã trở lại hoạt động.')->send();
                    }),
            ])
            ->emptyStateHeading('Chưa có bản phát hành')
            ->emptyStateIcon('heroicon-o-rocket-launch')
            ->striped();
    }
}
