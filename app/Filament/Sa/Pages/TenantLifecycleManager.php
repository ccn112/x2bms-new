<?php

namespace App\Filament\Sa\Pages;

use App\Models\Tenant;
use App\Models\TenantBackup;
use App\Support\Backup\TenantBackupService;
use App\Support\Backup\TenantOffboardService;
use App\Support\Backup\TenantRestoreService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * SA — Vòng đời & lưu trữ tenant: sao lưu, "off" (dormant, giữ bundle), khôi phục
 * (rehydrate). Chặn đăng nhập tenant dormant xử lý ở User::canAccessPanel.
 */
class TenantLifecycleManager extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static string|\UnitEnum|null $navigationGroup = 'Lưu trữ & Sao lưu';

    protected static ?string $navigationLabel = 'Vòng đời & Sao lưu tenant';

    protected static ?int $navigationSort = 30;

    protected static ?string $title = 'Vòng đời & Sao lưu tenant';

    protected static ?string $slug = 'tenant-lifecycle';

    protected string $view = 'filament.sa.pages.tenant-lifecycle';

    private const STATUS = [
        'active' => ['Đang hoạt động', 'success'],
        'dormant_archived' => ['Off (đã lưu trữ)', 'warning'],
        'purged' => ['Đã xóa', 'danger'],
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Tenant::query()->latest('id'))
            ->columns([
                TextColumn::make('code')->label('Mã')->searchable(),
                TextColumn::make('name')->label('Tên tenant')->searchable(),
                TextColumn::make('lifecycle_status')->label('Trạng thái')->badge()
                    ->formatStateUsing(fn (?string $state): string => self::STATUS[$state][0] ?? ($state ?? '—'))
                    ->color(fn (?string $state): string => self::STATUS[$state][1] ?? 'gray'),
                TextColumn::make('backups')->label('Số bản backup')->badge()->color('gray')
                    ->state(fn (Tenant $record): int => TenantBackup::where('tenant_id', $record->id)->count()),
                TextColumn::make('dormant_at')->label('Off lúc')->dateTime('d/m/Y H:i')->placeholder('—'),
                TextColumn::make('retention_until')->label('Giữ đến')->date('d/m/Y')->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('lifecycle_status')->label('Trạng thái')->options([
                    'active' => 'Đang hoạt động', 'dormant_archived' => 'Off (đã lưu trữ)', 'purged' => 'Đã xóa',
                ]),
            ])
            ->recordActions([
                Action::make('backup')->label('Sao lưu')->icon('heroicon-m-arrow-down-on-square')->color('gray')
                    ->requiresConfirmation()->modalDescription('Tạo bản backup (DB + files) cho tenant này ngay.')
                    ->action(function (Tenant $record): void {
                        $key = app(TenantBackupService::class)->create($record->id, now()->format('Ymd_His'), 'manual', auth()->id());
                        Notification::make()->title('Đã tạo bản backup')->body($key)->success()->send();
                    }),
                Action::make('offboard')->label('Off & lưu trữ')->icon('heroicon-m-moon')->color('warning')
                    ->visible(fn (Tenant $record): bool => $record->lifecycle_status === 'active')
                    ->requiresConfirmation()
                    ->modalHeading('Off tenant (dormant)')
                    ->modalDescription('Sẽ BACKUP rồi XÓA dữ liệu sống (DB + files) — GIỮ bundle để rehydrate. Nhân sự tenant sẽ không đăng nhập được cho tới khi khôi phục.')
                    ->action(function (Tenant $record): void {
                        $r = app(TenantOffboardService::class)->offboard($record->id, now()->format('Ymd_His'));
                        Notification::make()->title('Đã off tenant')->body('Purge '.array_sum($r['purged_tables']).' rows, '.$r['purged_files'].' files. Bundle giữ lại.')->success()->send();
                    }),
                Action::make('restore')->label('Khôi phục')->icon('heroicon-m-arrow-uturn-left')->color('success')
                    ->visible(fn (Tenant $record): bool => $record->lifecycle_status === 'dormant_archived')
                    ->requiresConfirmation()
                    ->modalHeading('Rehydrate tenant')
                    ->modalDescription('Khôi phục DB + files từ bản backup mới nhất và kích hoạt lại tenant.')
                    ->action(function (Tenant $record): void {
                        $bundle = app(TenantRestoreService::class)->latestBundle($record->id);
                        if (! $bundle) {
                            Notification::make()->title('Không có bản backup để khôi phục')->danger()->send();

                            return;
                        }
                        $res = app(TenantRestoreService::class)->restore($record->id, $bundle);
                        Notification::make()->title('Đã khôi phục tenant')->body('Rows: '.array_sum($res['tables']).' · files: '.$res['files'])->success()->send();
                    }),
            ]);
    }
}
