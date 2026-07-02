<?php

namespace App\Filament\Sa\Pages;

use App\Filament\Concerns\PlatformScreen;
use App\Models\SupportTicket;
use App\Models\TenantSupportProfile as ProfileModel;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

/**
 * WEB-UX-30-05 — Customer / Tenant Support Profile.
 * Danh sách hồ sơ hỗ trợ tenant; title click → chi tiết (plan/contacts/entitlements/ticket).
 */
class TenantSupportProfile extends Page implements HasTable
{
    use InteractsWithTable;
    use PlatformScreen;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|\UnitEnum|null $navigationGroup = 'Support Center';

    protected static ?string $navigationLabel = 'Hồ sơ hỗ trợ tenant';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Hồ sơ hỗ trợ khách hàng (tenant)';

    protected static ?string $slug = 'support/tenant-profiles';

    protected string $view = 'filament.pages.support-tenant-profile';

    protected function getViewData(): array
    {
        return [
            'kpis' => [
                ['label' => 'Tổng hồ sơ', 'value' => ProfileModel::count(), 'accent' => 'blue'],
                ['label' => 'Health TB', 'value' => number_format((float) ProfileModel::avg('health_score'), 1), 'accent' => 'green'],
                ['label' => 'CSAT TB', 'value' => number_format((float) ProfileModel::avg('csat'), 2), 'accent' => 'green'],
            ],
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(ProfileModel::query()->with('tenant'))
            ->columns([
                TextColumn::make('tenant.name')->label('Tenant')->searchable()->weight('medium')->color('primary')->action($this->detailAction()),
                TextColumn::make('support_plan')->label('Gói hỗ trợ')->badge()->color('info'),
                TextColumn::make('tier')->label('Tier')->toggleable(),
                TextColumn::make('health_score')->label('Health')->formatStateUsing(fn ($s) => number_format((float) $s, 1)),
                TextColumn::make('csat')->label('CSAT')->formatStateUsing(fn ($s) => number_format((float) $s, 2)),
            ])
            ->recordActions([$this->detailAction()])
            ->emptyStateHeading('Chưa có hồ sơ')
            ->striped();
    }

    public function detailAction(): Action
    {
        return Action::make('detail')->label('Chi tiết')->iconButton()->icon('heroicon-m-eye')->color('primary')
            ->modalHeading(fn (ProfileModel $r) => $r->tenant?->name)
            ->modalContent(fn (ProfileModel $r) => view('filament.pages.support-tenant-profile-detail', [
                'record' => $r->load(['tenant', 'accountManager', 'contacts', 'entitlements']),
                'tickets' => SupportTicket::where('tenant_id', $r->tenant_id)->latest('created_at')->limit(8)->get(),
            ]))
            ->modalWidth('2xl')->modalSubmitAction(false)->modalCancelActionLabel('Đóng');
    }
}
