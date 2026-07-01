<?php

namespace App\Filament\Concerns;

use App\Models\SharedPartner;
use App\Models\SharedPartnerCategory;
use App\Models\SharedPartnerCertification;
use App\Models\SharedPartnerProduct;
use App\Models\Tenant;
use App\Models\TenantPartnerAssignment;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

/**
 * WEB-UX-22-06/07 — Logic dùng chung cho thư viện đối tác nền tảng (nhà thầu & NCC).
 * Page dùng trait cần: `use WritesAudit;` và khai báo `partnerType()`.
 *
 * Platform sở hữu master; tenant chỉ GÁN (không sửa master, AC-13).
 * Đối tác blacklisted không gán được nếu không có quyền override (AC-14).
 */
trait SharedPartnerLibrary
{
    public const VERIFICATION = [
        'unverified' => ['Chưa xác minh', 'gray'],
        'verified' => ['Đã xác minh', 'success'],
        'preferred' => ['Ưu tiên', 'info'],
        'blacklisted' => ['Cấm', 'danger'],
    ];

    /** contractor | supplier */
    abstract protected function partnerType(): string;

    protected function getViewData(): array
    {
        $type = $this->partnerType();
        $c = fn (string $v) => SharedPartner::where('partner_type', $type)->where('verification_status', $v)->count();

        return [
            'title' => static::$title,
            'kpis' => [
                ['label' => 'Tổng đối tác', 'value' => SharedPartner::where('partner_type', $type)->count(), 'accent' => 'blue'],
                ['label' => 'Đã xác minh', 'value' => $c('verified'), 'accent' => 'green'],
                ['label' => 'Ưu tiên', 'value' => $c('preferred'), 'accent' => 'blue'],
                ['label' => 'Bị cấm', 'value' => $c('blacklisted'), 'accent' => 'red'],
            ],
        ];
    }

    /** @return array<\Filament\Forms\Components\Component> */
    protected function partnerForm(): array
    {
        return [
            TextInput::make('code')->label('Mã')->required()->maxLength(50),
            TextInput::make('name')->label('Tên đối tác')->required()->maxLength(255),
            Select::make('category_id')->label('Danh mục')
                ->options(fn () => SharedPartnerCategory::where('partner_type', $this->partnerType())->pluck('name', 'id')),
            TextInput::make('legal_name')->label('Tên pháp lý')->maxLength(255),
            TextInput::make('tax_code')->label('Mã số thuế')->maxLength(50),
            TextInput::make('contact_name')->label('Người liên hệ')->maxLength(255),
            TextInput::make('phone')->label('Điện thoại')->maxLength(30),
            TextInput::make('email')->label('Email')->email(),
            TextInput::make('service_area')->label('Khu vực phục vụ')->maxLength(255),
            Textarea::make('description')->label('Mô tả')->rows(2)->columnSpanFull(),
        ];
    }

    public function table(Table $table): Table
    {
        $isSupplier = $this->partnerType() === 'supplier';

        return $table
            ->query(SharedPartner::query()->where('partner_type', $this->partnerType())
                ->with('category')->withCount(['products', 'certifications']))
            ->defaultSort('name')
            ->columns(array_values(array_filter([
                TextColumn::make('name')->label('Đối tác')->searchable()->weight('medium')
                    ->description(fn (SharedPartner $p) => $p->category?->name),
                TextColumn::make('service_area')->label('Khu vực')->placeholder('—')->toggleable(),
                $isSupplier
                    ? TextColumn::make('products_count')->label('SP')->badge()->color('info')->alignCenter()
                    : TextColumn::make('certifications_count')->label('Chứng chỉ')->badge()->color('info')->alignCenter(),
                TextColumn::make('rating_avg')->label('Đánh giá')->badge()
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 1).' ★')
                    ->color(fn ($state) => $state >= 4 ? 'success' : ($state > 0 ? 'warning' : 'gray')),
                TextColumn::make('kpi_score')->label('KPI')->numeric(2)->alignCenter()->toggleable(),
                TextColumn::make('verification_status')->label('Trạng thái')->badge()
                    ->formatStateUsing(fn (string $state) => self::VERIFICATION[$state][0] ?? $state)
                    ->color(fn (string $state) => self::VERIFICATION[$state][1] ?? 'gray'),
            ])))
            ->filters([
                SelectFilter::make('verification_status')->label('Trạng thái')
                    ->options(collect(self::VERIFICATION)->map(fn ($v) => $v[0])->all()),
                SelectFilter::make('category_id')->label('Danh mục')
                    ->options(fn () => SharedPartnerCategory::where('partner_type', $this->partnerType())->pluck('name', 'id')),
            ])
            ->headerActions([
                Action::make('create')->label('Thêm đối tác')->icon('heroicon-m-plus')->color('primary')
                    ->schema($this->partnerForm())
                    ->action(function (array $data): void {
                        $data['partner_type'] = $this->partnerType();
                        $p = SharedPartner::create($data);
                        $this->audit('partner.create', 'Tạo đối tác: '.$p->name, SharedPartner::class, $p->id);
                        Notification::make()->title('Đã thêm đối tác')->success()->send();
                    }),
            ])
            ->recordActions(array_values(array_filter([
                $this->viewAction(),
                Action::make('edit')->label('Sửa')->iconButton()->icon('heroicon-m-pencil-square')->color('gray')
                    ->fillForm(fn (SharedPartner $p) => $p->only(['code', 'name', 'category_id', 'legal_name', 'tax_code', 'contact_name', 'phone', 'email', 'service_area', 'description']))
                    ->schema($this->partnerForm())
                    ->action(function (SharedPartner $p, array $data): void {
                        $p->update($data);
                        $this->audit('partner.update', 'Sửa đối tác: '.$p->name, SharedPartner::class, $p->id);
                        Notification::make()->title('Đã lưu')->success()->send();
                    }),
                Action::make('verify')->label('Xác minh')->iconButton()->icon('heroicon-m-check-badge')->color('success')
                    ->visible(fn (SharedPartner $p) => in_array($p->verification_status, ['unverified', 'blacklisted'], true))
                    ->requiresConfirmation()
                    ->action(fn (SharedPartner $p) => $this->setVerification($p, 'verified')),
                Action::make('prefer')->label('Đánh dấu ưu tiên')->iconButton()->icon('heroicon-m-star')->color('warning')
                    ->visible(fn (SharedPartner $p) => $p->verification_status === 'verified')
                    ->action(fn (SharedPartner $p) => $this->setVerification($p, 'preferred')),
                Action::make('blacklist')->label('Cấm')->iconButton()->icon('heroicon-m-no-symbol')->color('danger')
                    ->visible(fn (SharedPartner $p) => $p->verification_status !== 'blacklisted')
                    ->schema([Textarea::make('reason')->label('Lý do cấm')->required()->rows(2)])
                    ->action(function (SharedPartner $p, array $data): void {
                        $this->setVerification($p, 'blacklisted', $data['reason']);
                    }),
                $this->partnerType() === 'supplier'
                    ? Action::make('addProduct')->label('Thêm sản phẩm')->iconButton()->icon('heroicon-m-cube')->color('info')
                        ->schema([
                            TextInput::make('name')->label('Tên SP/vật tư')->required(),
                            TextInput::make('sku')->label('Mã SKU'),
                            TextInput::make('unit')->label('Đơn vị'),
                            TextInput::make('reference_price')->label('Giá tham khảo')->numeric()->default(0),
                            TextInput::make('warranty_months')->label('Bảo hành (tháng)')->numeric()->default(0),
                        ])
                        ->action(function (SharedPartner $p, array $data): void {
                            SharedPartnerProduct::create($data + ['partner_id' => $p->id]);
                            $this->audit('partner.product', 'Thêm SP cho '.$p->name, SharedPartner::class, $p->id);
                            Notification::make()->title('Đã thêm sản phẩm')->success()->send();
                        })
                    : Action::make('addCert')->label('Thêm chứng chỉ')->iconButton()->icon('heroicon-m-document-check')->color('info')
                        ->schema([
                            TextInput::make('name')->label('Tên chứng chỉ')->required(),
                            TextInput::make('certificate_no')->label('Số hiệu'),
                            TextInput::make('issued_by')->label('Đơn vị cấp'),
                            DatePicker::make('expired_at')->label('Hết hạn'),
                        ])
                        ->action(function (SharedPartner $p, array $data): void {
                            SharedPartnerCertification::create($data + ['partner_id' => $p->id]);
                            $this->audit('partner.cert', 'Thêm chứng chỉ cho '.$p->name, SharedPartner::class, $p->id);
                            Notification::make()->title('Đã thêm chứng chỉ')->success()->send();
                        }),
                Action::make('assign')->label('Gán cho công ty')->iconButton()->icon('heroicon-m-building-office')->color('gray')
                    ->schema([
                        Select::make('tenant_id')->label('Công ty')->required()->searchable()->options(fn () => Tenant::pluck('name', 'id')),
                        Select::make('assignment_type')->label('Loại gán')
                            ->options(['approved_vendor' => 'Được duyệt', 'contracted_vendor' => 'Có hợp đồng', 'favorite' => 'Ưu tiên', 'blacklist' => 'Cấm nội bộ'])
                            ->default('approved_vendor')->required(),
                    ])
                    ->action(fn (SharedPartner $p, array $data) => $this->assignPartner($p, $data)),
            ])))
            ->emptyStateHeading('Chưa có đối tác')
            ->emptyStateIcon('heroicon-o-briefcase')
            ->striped()
            ->paginated([10, 25, 50]);
    }

    public function viewAction(): Action
    {
        return Action::make('view')
            ->label('Chi tiết')->iconButton()->icon('heroicon-m-eye')->color('primary')
            ->modalHeading(fn (SharedPartner $p) => $p->name)
            ->modalContent(fn (SharedPartner $p) => view('filament.pages.partner-detail', [
                'record' => $p->load(['category', 'certifications', 'products']),
                'assignments' => TenantPartnerAssignment::withoutGlobalScope('tenant')->with('partner')
                    ->where('partner_id', $p->id)->get(),
                'verificationMap' => self::VERIFICATION,
            ]))
            ->modalSubmitAction(false)->modalCancelActionLabel('Đóng');
    }

    private function setVerification(SharedPartner $p, string $status, ?string $reason = null): void
    {
        $p->update(['verification_status' => $status]);
        $this->audit('partner.'.$status, self::VERIFICATION[$status][0].': '.$p->name.($reason ? ' — '.$reason : ''), SharedPartner::class, $p->id);
        Notification::make()->title(self::VERIFICATION[$status][0])->success()->send();
    }

    private function assignPartner(SharedPartner $p, array $data): void
    {
        // AC-14: đối tác bị cấm không gán được (trừ khi override).
        if ($p->verification_status === 'blacklisted' && $data['assignment_type'] !== 'blacklist' && ! Auth::user()->isPlatformAdmin()) {
            Notification::make()->title('Đối tác bị cấm — cần quyền override')->danger()->send();

            return;
        }

        TenantPartnerAssignment::withoutGlobalScope('tenant')->firstOrCreate(
            ['partner_id' => $p->id, 'tenant_id' => $data['tenant_id']],
            ['assignment_type' => $data['assignment_type']]
        );
        $this->audit('partner.assign', 'Gán '.$p->name.' → tenant #'.$data['tenant_id'], SharedPartner::class, $p->id);
        Notification::make()->title('Đã gán cho công ty')->success()->send();
    }
}
