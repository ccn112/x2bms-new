<?php

namespace App\Filament\Sa\Pages;

use App\Models\TenantBackup;
use App\Support\Storage\TenantStorage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * SA — Quản lý bản backup của các tenant: liệt kê, tải về, xóa (retention).
 */
class TenantBackupManager extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-circle-stack';

    protected static string|\UnitEnum|null $navigationGroup = 'Lưu trữ & Sao lưu';

    protected static ?string $navigationLabel = 'Bản sao lưu tenant';

    protected static ?int $navigationSort = 31;

    protected static ?string $title = 'Bản sao lưu tenant';

    protected static ?string $slug = 'tenant-backups';

    protected string $view = 'filament.sa.pages.tenant-backups';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => TenantBackup::query()->with('tenant')->latest())
            ->columns([
                TextColumn::make('tenant.name')->label('Tenant')->searchable()->sortable(),
                TextColumn::make('created_at')->label('Thời điểm')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('trigger')->label('Nguồn')->badge()->color('gray')
                    ->formatStateUsing(fn (string $state): string => ['manual' => 'Thủ công', 'offboard' => 'Off', 'scheduled' => 'Tự động'][$state] ?? $state),
                TextColumn::make('size_bytes')->label('Dung lượng')
                    ->formatStateUsing(fn (int $state): string => number_format($state / 1024, 0, ',', '.').' KB'),
                TextColumn::make('file_count')->label('Số file')->alignRight(),
                TextColumn::make('table_counts')->label('Tổng dòng DB')->alignRight()
                    ->state(fn (TenantBackup $record): int => array_sum((array) ($record->table_counts ?? []))),
                TextColumn::make('app_version')->label('Version')->badge()->color('gray'),
            ])
            ->recordActions([
                Action::make('download')->label('Tải về')->icon('heroicon-m-arrow-down-tray')->color('gray')
                    ->visible(fn (TenantBackup $record): bool => app(TenantStorage::class)->exists($record->path))
                    ->action(fn (TenantBackup $record): StreamedResponse => app(TenantStorage::class)->download(
                        $record->path, 'backup_'.($record->tenant?->code ?? $record->tenant_id).'_'.$record->created_at->format('Ymd_His').'.zip')),
                Action::make('delete')->label('Xóa')->icon('heroicon-m-trash')->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('Xóa vĩnh viễn bản backup này khỏi lưu trữ (không thể hoàn tác).')
                    ->action(function (TenantBackup $record): void {
                        app(TenantStorage::class)->disk()->delete($record->path);
                        $record->delete();
                        Notification::make()->title('Đã xóa bản backup')->success()->send();
                    }),
            ]);
    }
}
