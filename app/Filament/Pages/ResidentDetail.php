<?php

namespace App\Filament\Pages;

use App\Enums\FeedbackStatus;
use App\Filament\Concerns\ResetsResidentPassword;
use App\Models\AccessCard;
use App\Models\Apartment;
use App\Models\AuditLog;
use App\Models\Debt;
use App\Models\FeedbackRequest;
use App\Models\Resident;
use App\Models\ResidentApartmentRelation;
use App\Models\ResidentEmergencyContact;
use App\Models\Statement;
use App\Models\Vehicle;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;

/**
 * BQL-01-04 — Chi tiết cư dân 360 (bản giàu thông tin).
 * Khung DS-01 (đối xứng ApartmentProfile): title = tên cư dân ở topbar,
 * breadcrumb + action ở header Filament, KPI strip, section-tab.
 * 6 tab: Hồ sơ tổng quan · Căn hộ · Phương tiện & thẻ · Công nợ · Phản ánh · Nhật ký.
 */
class ResidentDetail extends Page
{
    use ResetsResidentPassword;

    protected static ?string $slug = 'residents/{resident}/detail';

    protected static bool $shouldRegisterNavigation = false;

    // Thuộc mục "Cư dân" → sidebar giữ mục cha active khi ở màn chi tiết.
    protected static ?string $navigationParentItem = 'Cư dân';

    protected string $view = 'filament.pages.resident-detail';

    public Resident $resident;

    public string $tab = 'overview';

    /** @var array<string, array{0:string,1:string}> status => [label, tone x-x2.status-badge] */
    private const STATUS = [
        'active' => ['Hoạt động', 'green'],
        'pending' => ['Chờ duyệt', 'amber'],
        'inactive' => ['Tạm khóa', 'red'],
    ];

    private const ROLE = ['owner' => 'Chủ sở hữu', 'tenant' => 'Người thuê', 'member' => 'Thành viên'];

    private const RELATIONSHIP = [
        'head' => 'Chủ hộ', 'owner' => 'Chủ sở hữu', 'spouse' => 'Vợ/Chồng',
        'child' => 'Con', 'parent' => 'Cha/Mẹ', 'sibling' => 'Anh/Chị/Em', 'other' => 'Khác',
    ];

    private const GENDER = ['male' => 'Nam', 'female' => 'Nữ', 'other' => 'Khác'];

    public function mount(Resident $resident): void
    {
        $this->resident = $resident->load(['apartmentRelations.apartment.floor', 'apartmentRelations.apartment.building', 'building', 'emergencyContacts']);
    }

    public function getTitle(): string
    {
        return $this->resident->full_name;
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => $this->crumb('heroicon-m-home', 'Tổng quan'),
            url('/admin/residents') => $this->crumb('heroicon-m-users', 'Cư dân'),
            $this->crumb('heroicon-m-user', $this->resident->full_name),
        ];
    }

    private function crumb(string $icon, string $label): HtmlString
    {
        return new HtmlString('<span class="inline-flex items-center gap-1">'
            .svg($icon, 'h-4 w-4 shrink-0 opacity-70')->toHtml()
            .'<span>'.e($label).'</span></span>');
    }

    protected function getHeaderActions(): array
    {
        $r = $this->resident;

        return [
            Action::make('edit')->label('Chỉnh sửa')->icon('heroicon-m-pencil-square')->color('gray')
                ->slideOver()->modalWidth('2xl')->modalHeading('Chỉnh sửa cư dân — '.$r->full_name)
                ->fillForm(fn () => $r->only([
                    'full_name', 'dob', 'gender', 'phone', 'contact_phone', 'email', 'contact_email',
                    'id_no', 'id_issued_date', 'id_issued_place', 'nationality', 'marital_status',
                    'occupation', 'relationship_to_head', 'residence_status', 'contact_address', 'mailing_address', 'note',
                ]))
                ->schema($this->editForm())
                ->action(function (array $data): void {
                    $this->resident->update($data);
                    $this->resident->refresh()->load(['apartmentRelations.apartment.floor', 'apartmentRelations.apartment.building', 'building', 'emergencyContacts']);
                    $this->audit('resident.update', 'Cập nhật hồ sơ cư dân '.$this->resident->full_name);
                    Notification::make()->title('Đã lưu thay đổi hồ sơ '.$this->resident->full_name)->success()->send();
                }),
            Action::make('notify')->label('Gửi thông báo')->icon('heroicon-m-megaphone')->color('gray')
                ->modalWidth('md')->modalHeading('Gửi thông báo — '.$r->full_name)
                ->schema([
                    TextInput::make('title')->label('Tiêu đề')->required(),
                    Textarea::make('body')->label('Nội dung')->required()->rows(4),
                ])
                ->action(function (array $data): void {
                    $this->audit('resident.notify', 'Gửi thông báo cho '.$this->resident->full_name.': '.$data['title']);
                    Notification::make()->title('Đã gửi thông báo tới '.$this->resident->full_name)->success()->send();
                }),
            Action::make('resetPassword')->label('Đặt lại mật khẩu')->icon('heroicon-m-key')->color('warning')
                ->modalWidth('md')->modalHeading('Đặt lại mật khẩu — '.$r->full_name)
                ->modalSubmitActionLabel('Thực hiện')
                ->schema($this->resetPasswordSchema())
                ->action(fn (array $data) => $this->handleResidentPasswordReset($this->resident, $data)),
            // Nhóm phụ (ít dùng / nhạy cảm) gom vào dropdown "Thao tác khác".
            ActionGroup::make([
            Action::make('addRelation')->label('Thêm quan hệ')->icon('heroicon-m-user-plus')->color('gray')
                ->modalWidth('md')->modalHeading('Thêm liên hệ / quan hệ — '.$r->full_name)
                ->schema([
                    TextInput::make('full_name')->label('Họ và tên')->required(),
                    Select::make('relationship')->label('Mối quan hệ')->options(self::RELATIONSHIP)->required(),
                    TextInput::make('phone')->label('Số điện thoại')->tel(),
                    TextInput::make('email')->label('Email')->email(),
                    Textarea::make('note')->label('Ghi chú')->rows(2),
                ])
                ->action(function (array $data): void {
                    ResidentEmergencyContact::create([
                        'tenant_id' => $this->resident->tenant_id,
                        'resident_id' => $this->resident->id,
                        'full_name' => $data['full_name'],
                        'relationship' => self::RELATIONSHIP[$data['relationship']] ?? $data['relationship'],
                        'phone' => $data['phone'] ?? null,
                        'email' => $data['email'] ?? null,
                        'note' => $data['note'] ?? null,
                    ]);
                    $this->resident->refresh()->load('emergencyContacts');
                    $this->audit('resident.add_relation', 'Thêm quan hệ '.$data['full_name'].' cho '.$this->resident->full_name);
                    Notification::make()->title('Đã thêm quan hệ')->success()->send();
                }),
            Action::make('requestUpdate')->label('Yêu cầu cập nhật')->icon('heroicon-m-arrow-path')->color('gray')
                ->modalWidth('md')->modalHeading('Yêu cầu cư dân cập nhật thông tin')
                ->schema([
                    Select::make('fields')->label('Nội dung cần cập nhật')->multiple()
                        ->options([
                            'id_no' => 'Giấy tờ tùy thân (CCCD)',
                            'contact' => 'Thông tin liên hệ (SĐT/email)',
                            'address' => 'Địa chỉ',
                            'portrait' => 'Ảnh chân dung',
                            'other' => 'Khác',
                        ])->required(),
                    Textarea::make('reason')->label('Ghi chú gửi cư dân')->rows(3),
                ])
                ->action(function (array $data): void {
                    $this->audit('resident.request_update', 'Yêu cầu '.$this->resident->full_name.' cập nhật: '.implode(', ', $data['fields']));
                    Notification::make()->title('Đã gửi yêu cầu cập nhật tới cư dân')->success()->send();
                }),
            Action::make('lock')->label('Khóa tài khoản')->icon('heroicon-m-lock-closed')->color('danger')
                ->visible(fn () => $this->resident->status !== 'inactive')
                ->requiresConfirmation()->modalHeading('Khóa tài khoản cư dân')
                ->modalDescription('Cư dân sẽ không thể đăng nhập cho tới khi được mở khóa.')
                ->schema([Textarea::make('reason')->label('Lý do khóa')->required()->rows(3)])
                ->action(function (array $data): void {
                    $this->resident->update(['status' => 'inactive']);
                    $this->resident->refresh();
                    $this->audit('resident.lock', 'Khóa tài khoản '.$this->resident->full_name.': '.$data['reason']);
                    Notification::make()->title('Đã khóa tài khoản cư dân')->warning()->send();
                }),
            Action::make('unlock')->label('Mở khóa tài khoản')->icon('heroicon-m-lock-open')->color('success')
                ->visible(fn () => $this->resident->status === 'inactive')
                ->requiresConfirmation()->modalHeading('Mở khóa tài khoản cư dân')
                ->action(function (): void {
                    $this->resident->update(['status' => 'active']);
                    $this->resident->refresh();
                    $this->audit('resident.unlock', 'Mở khóa tài khoản '.$this->resident->full_name);
                    Notification::make()->title('Đã mở khóa tài khoản')->success()->send();
                }),
            Action::make('export')->label('Xuất hồ sơ')->icon('heroicon-m-arrow-down-tray')->color('gray')
                ->action('exportDossier'),
            ])->label('Thao tác khác')->icon('heroicon-m-ellipsis-horizontal')->button()->color('gray'),
        ];
    }

    /** Form chỉnh sửa cư dân (bespoke /admin, slide-over — theme DS-01). */
    private function editForm(): array
    {
        return [
            Section::make('Thông tin cá nhân')->schema([
                Grid::make(2)->schema([
                    TextInput::make('full_name')->label('Họ và tên')->required(),
                    DatePicker::make('dob')->label('Ngày sinh')->native(false),
                    Select::make('gender')->label('Giới tính')->options(self::GENDER),
                    TextInput::make('marital_status')->label('Tình trạng hôn nhân'),
                    TextInput::make('nationality')->label('Quốc tịch'),
                    TextInput::make('occupation')->label('Nghề nghiệp'),
                    Select::make('relationship_to_head')->label('Quan hệ với chủ hộ')->options(self::RELATIONSHIP),
                    Select::make('residence_status')->label('Trạng thái cư trú')
                        ->options(['permanent' => 'Thường trú', 'temporary' => 'Tạm trú', 'absent' => 'Vắng mặt']),
                ]),
            ]),
            Section::make('Giấy tờ tùy thân')->schema([
                Grid::make(2)->schema([
                    TextInput::make('id_no')->label('Số CMND/CCCD'),
                    DatePicker::make('id_issued_date')->label('Ngày cấp')->native(false),
                    TextInput::make('id_issued_place')->label('Nơi cấp')->columnSpanFull(),
                ]),
            ])->collapsed(),
            Section::make('Liên hệ')->schema([
                Grid::make(2)->schema([
                    TextInput::make('phone')->label('Số điện thoại')->tel(),
                    TextInput::make('contact_phone')->label('SĐT khác')->tel(),
                    TextInput::make('email')->label('Email')->email(),
                    TextInput::make('contact_email')->label('Email khác')->email(),
                    Textarea::make('contact_address')->label('Địa chỉ hiện tại')->rows(2),
                    Textarea::make('mailing_address')->label('Địa chỉ thường trú')->rows(2),
                ]),
            ])->collapsed(),
            Textarea::make('note')->label('Ghi chú')->rows(2),
        ];
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
    }

    private function apartmentIds(): array
    {
        return $this->resident->apartmentRelations->pluck('apartment_id')->filter()->all() ?: [0];
    }

    /** Xuất hồ sơ cư dân (CSV thật): thông tin cá nhân + căn hộ + xe + thẻ + công nợ. */
    public function exportDossier()
    {
        $r = $this->resident;
        $this->audit('resident.export_dossier', 'Xuất hồ sơ cư dân '.$r->full_name);

        $primary = $r->primaryRelation();
        $ap = $primary?->apartment;
        $vehicles = Vehicle::where('resident_id', $r->id)->get();
        $cards = AccessCard::where('resident_id', $r->id)->get();
        $overdue = (float) Debt::whereIn('apartment_id', $this->apartmentIds())->where('is_overdue', true)->sum('amount');
        $filename = 'ho_so_cu_dan_'.str_replace(['/', ' '], '_', $r->code ?: (string) $r->id).'_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($r, $ap, $primary, $vehicles, $cards, $overdue) {
            $out = fopen('php://output', 'w');
            fwrite($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, ['HỒ SƠ CƯ DÂN', $r->full_name]);
            fputcsv($out, ['Mã cư dân', $r->code ?? '']);
            fputcsv($out, ['Ngày sinh', $r->dob?->format('d/m/Y') ?? '']);
            fputcsv($out, ['Giới tính', self::GENDER[$r->gender] ?? $r->gender ?? '']);
            fputcsv($out, ['Số CMND/CCCD', $r->id_no ?? '']);
            fputcsv($out, ['Số điện thoại', $r->phone ?? $r->contact_phone ?? '']);
            fputcsv($out, ['Email', $r->email ?? $r->contact_email ?? '']);
            fputcsv($out, ['Quốc tịch', $r->nationality ?? '']);
            fputcsv($out, ['Nghề nghiệp', $r->occupation ?? '']);
            fputcsv($out, ['Địa chỉ thường trú', $r->mailing_address ?? '']);
            fputcsv($out, ['Căn hộ', $ap?->code ?? '']);
            fputcsv($out, ['Vai trò', self::ROLE[$primary?->role] ?? '']);
            fputcsv($out, ['Công nợ quá hạn', number_format($overdue, 0, ',', '.')]);
            fputcsv($out, []);

            fputcsv($out, ['PHƯƠNG TIỆN']);
            fputcsv($out, ['Biển số', 'Loại', 'Thẻ giữ xe']);
            foreach ($vehicles as $v) {
                fputcsv($out, [$v->plate_no, optional($v->type)->label() ?? $v->type, $v->parking_card_no ?? '']);
            }
            fputcsv($out, []);

            fputcsv($out, ['THẺ RA VÀO']);
            fputcsv($out, ['Mã thẻ', 'Loại', 'Trạng thái']);
            foreach ($cards as $c) {
                $st = $c->status instanceof \BackedEnum ? $c->status->value : $c->status;
                fputcsv($out, [$c->card_no, $c->is_biometric ? 'Sinh trắc' : 'RFID', $st]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    protected function getViewData(): array
    {
        $r = $this->resident;
        $ids = $this->apartmentIds();
        $primary = $r->primaryRelation();
        $ap = $primary?->apartment;

        // Household members = mọi cư dân gắn cùng (các) căn hộ (kể cả cư dân này).
        $relations = ResidentApartmentRelation::whereIn('apartment_id', $ids)
            ->with('resident')->get()->filter(fn ($rel) => $rel->resident);
        $members = $relations->unique(fn ($rel) => $rel->resident->id)->values();

        // Finance
        $currentDebt = (float) Debt::whereIn('apartment_id', $ids)->where('is_overdue', true)->sum('amount');
        $debtCount = Debt::whereIn('apartment_id', $ids)->where('is_overdue', true)->count();
        $totalPaid = (float) Statement::whereIn('apartment_id', $ids)->sum('paid_amount');
        $totalBilled = (float) Statement::whereIn('apartment_id', $ids)->sum('total_amount');
        $feeThisMonth = (float) Statement::whereIn('apartment_id', $ids)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('total_amount');
        $nextDue = Statement::whereIn('apartment_id', $ids)
            ->whereColumn('paid_amount', '<', 'total_amount')->whereNotNull('due_date')
            ->where('due_date', '>=', now()->toDateString())->min('due_date');

        // Phản ánh
        $fbBase = FeedbackRequest::whereIn('apartment_id', $ids);
        $fbOpen = (clone $fbBase)->whereIn('status', FeedbackStatus::pendingValues())->count();
        $feedback = (clone $fbBase)->latest()->limit(10)->get()->map(fn ($f) => [
            'code' => $f->code,
            'title' => $f->title,
            'status' => $f->status instanceof \BackedEnum ? $f->status->value : $f->status,
            'priority' => $f->priority instanceof \BackedEnum ? $f->priority->value : $f->priority,
            'date' => $f->created_at?->format('d/m/Y'),
        ])->all();

        $vehicles = Vehicle::where('resident_id', $r->id)->with('resident')->get();
        $cards = AccessCard::where('resident_id', $r->id)->get();

        [$stLabel, $stTone] = self::STATUS[$r->status] ?? [$r->status, 'slate'];
        $age = $r->dob ? Carbon::parse($r->dob)->age : null;

        return [
            'r' => $r,
            'statusLabel' => $stLabel,
            'statusTone' => $stTone,
            'roleLabel' => self::ROLE[$primary?->role] ?? '—',
            'apartment' => $ap,
            'fbOpen' => $fbOpen,
            'feedback' => $feedback,
            'kpis' => [
                ['label' => 'Mã cư dân', 'value' => $r->code ?? '—', 'sub' => $r->building?->name ?? 'Sunshine Garden'],
                ['label' => 'Trạng thái', 'value' => $stLabel, 'tone' => $stTone, 'badge' => true],
                ['label' => 'Căn hộ', 'value' => $ap?->code ?? 'Chưa gắn', 'link' => $ap ? ['setTab', 'apartment', 'Xem căn hộ'] : null],
                ['label' => 'Vai trò', 'value' => self::ROLE[$primary?->role] ?? '—'],
                ['label' => 'Thành viên hộ', 'value' => (string) $members->count()],
                ['label' => 'Công nợ hiện tại', 'value' => number_format($currentDebt, 0, ',', '.').' đ', 'warn' => $currentDebt > 0, 'link' => ['setTab', 'finance', 'Xem công nợ']],
                ['label' => 'Phản ánh đang mở', 'value' => (string) $fbOpen, 'link' => ['setTab', 'feedback', 'Xem danh sách']],
            ],
            // Tab Hồ sơ tổng quan
            'personal' => [
                ['Họ và tên', $r->full_name],
                ['Giới tính', self::GENDER[$r->gender] ?? $r->gender ?? '—'],
                ['Số CMND/CCCD', $r->id_no ?? '—', $r->id_no ? true : false],
                ['Ngày cấp', $r->id_issued_date?->format('d/m/Y') ?? '—'],
                ['Nơi cấp', $r->id_issued_place ?? '—'],
                ['Quốc tịch', $r->nationality ?? '—'],
                ['Địa chỉ thường trú', $r->mailing_address ?? '—'],
                ['Địa chỉ hiện tại', $r->contact_address ?? '—'],
                ['Nghề nghiệp', $r->occupation ?? '—'],
                ['Email liên hệ khác', $r->contact_email ?? '—'],
                ['Ghi chú', $r->note ?? '—'],
            ],
            'quick' => [
                ['Vai trò trong hộ', self::RELATIONSHIP[$r->relationship_to_head] ?? (self::ROLE[$primary?->role] ?? '—')],
                ['Số điện thoại', $r->phone ?? $r->contact_phone ?? '—'],
                ['Email', $r->email ?? '—'],
                ['Ngày sinh', $r->dob ? $r->dob->format('d/m/Y').($age !== null ? ' ('.$age.' tuổi)' : '') : '—'],
                ['Ngày đăng ký', $r->join_date?->format('d/m/Y') ?? $r->created_at?->format('d/m/Y') ?? '—'],
                ['Nguồn đăng ký', $r->source ?? 'Đăng ký trực tiếp'],
                ['Trạng thái KYC', $r->kyc_status ?? '—'],
            ],
            'apartmentInfo' => $ap ? [
                ['Căn hộ', $ap->code],
                ['Dự án', $ap->building?->project?->name ?? 'Sunshine Garden'],
                ['Tòa / Block', $ap->building?->name ?? '—'],
                ['Loại căn hộ', $ap->type ?? '—'],
                ['Diện tích', $ap->area_sqm ? number_format((float) $ap->area_sqm, 1, ',', '.').' m²' : '—'],
                ['Tình trạng', ($ap->status === 'occupied' ? 'Đang ở' : $ap->status) ?? '—'],
                ['Ngày ký HĐ', $ap->contract_signed_at?->format('d/m/Y') ?? '—'],
                ['Ngày nhận nhà', $ap->handover_date?->format('d/m/Y') ?? '—'],
                ['Hình thức sở hữu', $ap->ownership_type ?: 'Sở hữu'],
                ['Ngày bắt đầu cư trú', $primary?->start_date ? Carbon::parse($primary->start_date)->format('d/m/Y') : '—'],
            ] : [],
            'members' => $this->memberRows($members, $r->id),
            'emergencyContacts' => $r->emergencyContacts,
            'vehicles' => $vehicles,
            'cards' => $cards,
            'finance' => [
                'feeThisMonth' => $feeThisMonth,
                'currentDebt' => $currentDebt,
                'debtCount' => $debtCount,
                'totalPaid' => $totalPaid,
                'totalBilled' => $totalBilled,
                'nextDue' => $nextDue ? Carbon::parse($nextDue)->format('d/m/Y') : '—',
                'nextDays' => $nextDue ? (int) round(now()->startOfDay()->diffInDays(Carbon::parse($nextDue), false)) : null,
            ],
            'aiSuggestions' => $this->aiSuggestions($r, $currentDebt, $debtCount, $cards),
            'timeline' => $this->timeline($r, $members, $vehicles, $cards),
        ];
    }

    private function memberRows($members, int $selfId): array
    {
        return $members->map(fn ($rel) => [
            'id' => $rel->resident->id,
            'name' => $rel->resident->full_name.($rel->resident->id === $selfId ? ' (cư dân này)' : ''),
            'relationship' => self::RELATIONSHIP[$rel->resident->relationship_to_head] ?? (self::ROLE[$rel->role] ?? '—'),
            'relationshipTone' => $rel->role === 'owner' ? 'blue' : 'slate',
            'role' => $rel->is_primary ? 'Chủ hộ' : 'Thành viên',
            'dob' => $rel->resident->dob ? Carbon::parse($rel->resident->dob)->format('d/m/Y') : '—',
            'phone' => $rel->resident->phone ?? $rel->resident->contact_phone ?? '—',
        ])->values()->all();
    }

    /** Gợi ý AI = rule-based inline (không LLM), theo quyết định đã chốt. */
    private function aiSuggestions(Resident $r, float $debt, int $debtCount, $cards): array
    {
        $out = [];
        if (blank($r->id_no)) {
            $out[] = ['tone' => 'amber', 'title' => 'Thiếu giấy tờ tùy thân', 'detail' => 'Hồ sơ chưa có số CMND/CCCD — nên gửi yêu cầu cập nhật để đủ điều kiện KYC.'];
        }
        if ($debt > 0) {
            $out[] = ['tone' => 'amber', 'title' => 'Công nợ cần thu', 'detail' => 'Có '.$debtCount.' khoản phí quá hạn ('.number_format($debt, 0, ',', '.').' đ) — cân nhắc nhắc phí.'];
        }
        foreach ($cards as $c) {
            if ($c->valid_to && Carbon::parse($c->valid_to)->between(now(), now()->addDays(30))) {
                $out[] = ['tone' => 'blue', 'title' => 'Thẻ sắp hết hạn', 'detail' => 'Thẻ '.$c->card_no.' sẽ hết hạn '.Carbon::parse($c->valid_to)->format('d/m/Y').' — nên gia hạn.'];
                break;
            }
        }
        if (! $out) {
            $out[] = ['tone' => 'green', 'title' => 'Hồ sơ đầy đủ', 'detail' => 'Không phát hiện vấn đề nào cần xử lý cho cư dân này.'];
        }

        return $out;
    }

    private function timeline(Resident $r, $members, $vehicles, $cards): array
    {
        $events = [];
        $events[] = ['at' => $r->created_at, 'dot' => 'gray', 'title' => 'Tạo hồ sơ cư dân', 'detail' => 'Khởi tạo hồ sơ '.$r->full_name, 'actor' => 'Hệ thống'];
        if ($r->join_date) {
            $events[] = ['at' => $r->join_date, 'dot' => 'blue', 'title' => 'Đăng ký cư trú', 'detail' => 'Nguồn: '.($r->source ?? 'Đăng ký trực tiếp'), 'actor' => ''];
        }
        foreach ($vehicles as $v) {
            $events[] = ['at' => $v->created_at, 'dot' => 'green', 'title' => 'Đăng ký phương tiện '.$v->plate_no, 'detail' => 'Loại: '.(optional($v->type)->label() ?? $v->type), 'actor' => ''];
        }
        foreach ($cards as $c) {
            $events[] = ['at' => $c->created_at, 'dot' => 'purple', 'title' => 'Cấp thẻ ra vào #'.$c->card_no, 'detail' => $c->valid_to ? 'Hiệu lực đến: '.Carbon::parse($c->valid_to)->format('d/m/Y') : '', 'actor' => ''];
        }
        // Audit thật liên quan tòa (mềm)
        foreach (AuditLog::where('building_id', $r->building_id)->latest()->take(6)->get() as $al) {
            $events[] = ['at' => $al->created_at, 'dot' => 'orange', 'title' => $al->description, 'detail' => '', 'actor' => $al->actor_name ?? ''];
        }
        usort($events, fn ($x, $y) => $y['at'] <=> $x['at']);

        return array_map(fn ($e) => [
            'date' => $e['at'] ? Carbon::parse($e['at'])->format('d/m/Y') : '—',
            'time' => $e['at'] ? Carbon::parse($e['at'])->format('H:i') : '',
            'dot' => $e['dot'], 'title' => $e['title'], 'detail' => $e['detail'], 'actor' => $e['actor'],
        ], array_slice($events, 0, 12));
    }

    private function audit(string $action, string $description): void
    {
        $user = auth()->user();
        AuditLog::create([
            'tenant_id' => $user?->tenant_id, 'building_id' => $user?->building_id, 'user_id' => $user?->id,
            'actor_name' => $user?->name, 'action' => $action, 'subject_type' => 'Resident',
            'subject_id' => $this->resident->id, 'description' => $description,
        ]);
    }
}
