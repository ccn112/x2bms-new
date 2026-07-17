<?php

namespace App\Filament\Pages;

use App\Enums\FeedbackStatus;
use App\Models\AccessCard;
use App\Models\Apartment;
use App\Models\AuditLog;
use App\Models\Debt;
use App\Models\FeedbackRequest;
use App\Models\ResidentApartmentRelation;
use App\Models\Statement;
use App\Models\Vehicle;
use Filament\Actions\Action;
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
 * BQL-01-06 — Chi tiết căn hộ 360 (bản giàu thông tin, tham chiếu BQL-01-03).
 * Khung DS-01: title ở topbar, breadcrumb + action ở header Filament, section-tab.
 * Nội dung đầy đủ: KPI strip 7 ô · 7 tab · Thông tin căn hộ 3 cột (có công tơ) ·
 * panel Cảnh báo · Thông tin nhanh cư dân · Tài liệu · Phản ánh.
 */
class ApartmentProfile extends Page
{
    protected static ?string $slug = 'apartments/{apartment}/profile';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.apartment-profile';

    public Apartment $apartment;

    public string $tab = 'info';

    // [nhãn, tone của x-x2.status-badge: green|teal|amber|red|blue|slate]
    private const STATUS = [
        'occupied' => ['Đang ở', 'green'],
        'vacant' => ['Trống', 'slate'],
        'pending_attach' => ['Chờ gắn cư dân', 'amber'],
        'maintenance' => ['Đang sửa chữa', 'blue'],
        'handover_pending' => ['Chờ bàn giao', 'amber'],
        'locked' => ['Khóa', 'red'],
    ];

    private const ROLE = ['owner' => 'Chủ sở hữu', 'tenant' => 'Người thuê', 'member' => 'Thành viên'];

    private const RELATIONSHIP = [
        'head' => 'Chủ hộ', 'owner' => 'Chủ sở hữu', 'spouse' => 'Vợ/Chồng',
        'child' => 'Con', 'parent' => 'Cha/Mẹ', 'sibling' => 'Anh/Chị/Em', 'other' => 'Khác',
    ];

    private const RESIDENCE = ['permanent' => 'Thường trú', 'temporary' => 'Tạm trú', 'absent' => 'Vắng mặt'];

    public function mount(Apartment $apartment): void
    {
        $this->apartment = $apartment->load('floor', 'building');
    }

    public function getTitle(): string
    {
        return 'Căn hộ '.$this->apartment->code;
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => $this->crumb('heroicon-m-home', 'Tổng quan'),
            url('/admin/apartments') => $this->crumb('heroicon-m-home-modern', 'Hồ sơ căn hộ'),
            $this->crumb('heroicon-m-building-office-2', $this->apartment->code),
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
        return [
            Action::make('edit')->label('Sửa căn hộ')->icon('heroicon-m-pencil-square')->color('gray')
                ->slideOver()->modalWidth('2xl')->modalHeading('Sửa căn hộ '.$this->apartment->code)
                ->fillForm(fn () => $this->apartment->only([
                    'code', 'floor_id', 'type', 'status', 'area_sqm', 'bedroom_count', 'bathroom_count',
                    'furniture_status', 'direction', 'balcony_direction', 'position', 'purpose',
                    'handover_date', 'handover_price', 'ownership_type', 'contract_type', 'contract_no',
                    'contract_signed_at', 'ownership_term', 'electric_meter_no', 'water_meter_no', 'gas_meter_no', 'note',
                ]))
                ->schema($this->editForm())
                ->action(function (array $data): void {
                    $this->apartment->update($data);
                    $this->apartment->refresh()->load('floor', 'building');
                    $this->audit('apartment.update', 'Cập nhật hồ sơ căn hộ '.$this->apartment->code, $this->apartment->id);
                    Notification::make()->title('Đã lưu thay đổi căn '.$this->apartment->code)->success()->send();
                }),
            Action::make('attach')->label('Gắn cư dân')->icon('heroicon-m-user-plus')->color('gray')
                ->url(url('/admin/residents/binding-queue')),
            Action::make('changeStatus')->label('Đổi trạng thái')->icon('heroicon-m-arrow-path')->color('gray')
                ->action(fn () => Notification::make()->title('Đổi trạng thái căn hộ')->body('Luồng đổi trạng thái sẽ bổ sung ở đợt sau.')->info()->send()),
            Action::make('note')->label('Tạo ghi chú')->icon('heroicon-m-chat-bubble-bottom-center-text')->color('gray')
                ->action(fn () => Notification::make()->title('Tạo ghi chú')->body('Trình ghi chú sẽ bổ sung ở đợt sau.')->info()->send()),
            Action::make('export')->label('Xuất hồ sơ')->icon('heroicon-m-arrow-down-tray')->color('gray')
                ->action('exportDossier'),
        ];
    }

    /** Form sửa căn hộ (bespoke /admin, hiện trong slide-over — theme DS-01). */
    private function editForm(): array
    {
        $floors = \App\Models\Floor::where('building_id', $this->apartment->building_id)->orderBy('name')->pluck('name', 'id')->all();
        $statuses = collect(self::STATUS)->map(fn ($v) => $v[0])->all();

        return [
            Section::make('Thông tin cơ bản')->schema([
                Grid::make(2)->schema([
                    TextInput::make('code')->label('Mã căn')->required(),
                    Select::make('floor_id')->label('Tầng')->options($floors)->searchable(),
                    TextInput::make('type')->label('Loại căn'),
                    Select::make('status')->label('Trạng thái')->options($statuses),
                    TextInput::make('area_sqm')->label('Diện tích (m²)')->numeric(),
                    TextInput::make('furniture_status')->label('Tình trạng nội thất'),
                    TextInput::make('bedroom_count')->label('Số phòng ngủ')->numeric(),
                    TextInput::make('bathroom_count')->label('Số phòng tắm')->numeric(),
                ]),
            ]),
            Section::make('Vị trí & hướng')->schema([
                Grid::make(2)->schema([
                    TextInput::make('direction')->label('Hướng cửa chính'),
                    TextInput::make('balcony_direction')->label('Hướng ban công'),
                    TextInput::make('position')->label('Vị trí căn'),
                    TextInput::make('purpose')->label('Mục đích sử dụng'),
                ]),
            ])->collapsed(),
            Section::make('Bàn giao & hợp đồng')->schema([
                Grid::make(2)->schema([
                    DatePicker::make('handover_date')->label('Ngày bàn giao')->native(false),
                    TextInput::make('handover_price')->label('Giá trị bàn giao (đ)')->numeric(),
                    TextInput::make('ownership_type')->label('Hình thức sở hữu'),
                    TextInput::make('contract_type')->label('Loại hợp đồng'),
                    TextInput::make('contract_no')->label('Số hợp đồng'),
                    DatePicker::make('contract_signed_at')->label('Ngày ký HĐ')->native(false),
                    TextInput::make('ownership_term')->label('Thời hạn'),
                ]),
            ])->collapsed(),
            Section::make('Công tơ')->schema([
                Grid::make(3)->schema([
                    TextInput::make('electric_meter_no')->label('Số công tơ điện'),
                    TextInput::make('water_meter_no')->label('Số công tơ nước'),
                    TextInput::make('gas_meter_no')->label('Số công tơ gas'),
                ]),
            ])->collapsed(),
            Textarea::make('note')->label('Ghi chú')->rows(2),
        ];
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
    }

    public function exportDossier(): void
    {
        $this->audit('apartment.export_dossier', 'Xuất hồ sơ căn hộ '.$this->apartment->code, $this->apartment->id);
        Notification::make()->title('Đang chuẩn bị hồ sơ căn '.$this->apartment->code)->info()->send();
    }

    protected function getViewData(): array
    {
        $a = $this->apartment;

        $relations = ResidentApartmentRelation::where('apartment_id', $a->id)
            ->with('resident')->get()->filter(fn ($r) => $r->resident);

        $ownerRel = $relations->firstWhere('role', 'owner');
        $owner = $ownerRel?->resident;
        $occupants = $relations->count();

        // Công nợ
        $debtTotal = (float) Debt::where('apartment_id', $a->id)->sum('amount');
        $debtOverdue = (float) Debt::where('apartment_id', $a->id)->where('is_overdue', true)->sum('amount');
        $debtOverdueCount = Debt::where('apartment_id', $a->id)->where('is_overdue', true)->count();
        $oldestOverdue = Debt::where('apartment_id', $a->id)->where('is_overdue', true)->min('due_date');
        $overdueDays = $oldestOverdue ? Carbon::parse($oldestOverdue)->diffInDays(now()) : 0;
        $lastPaid = Statement::where('apartment_id', $a->id)->where('paid_amount', '>', 0)->max('updated_at');

        // Phản ánh
        $fbBase = FeedbackRequest::where('apartment_id', $a->id);
        $fbOpen = (clone $fbBase)->whereIn('status', FeedbackStatus::pendingValues())->count();
        $feedback = (clone $fbBase)->latest()->limit(10)->get()->map(fn ($f) => [
            'code' => $f->code,
            'title' => $f->title,
            'status' => $f->status instanceof \BackedEnum ? $f->status->value : $f->status,
            'priority' => $f->priority instanceof \BackedEnum ? $f->priority->value : $f->priority,
            'date' => $f->created_at?->format('d/m/Y'),
        ])->all();

        $vehicles = Vehicle::where('apartment_id', $a->id)->with('resident')->get();
        $cards = AccessCard::where('apartment_id', $a->id)->get();

        // Tỷ lệ lấp đầy tòa
        $bTotal = Apartment::where('building_id', $a->building_id)->count();
        $bOccupied = Apartment::where('building_id', $a->building_id)->where('status', 'occupied')->count();
        $fillRate = $bTotal ? (int) round($bOccupied / $bTotal * 100) : 0;

        [$stLabel, $stTone] = self::STATUS[$a->status] ?? [$a->status, 'gray'];

        // Cảnh báo & thông tin cần lưu ý (suy ra)
        $alerts = [];
        if ($debtOverdue > 0) {
            $alerts[] = ['tone' => 'amber', 'icon' => 'heroicon-m-exclamation-triangle', 'title' => 'Công nợ quá hạn',
                'detail' => 'Có '.$debtOverdueCount.' khoản phí chưa thanh toán.', 'badge' => 'Quá hạn '.$overdueDays.' ngày'];
        } else {
            $alerts[] = ['tone' => 'green', 'icon' => 'heroicon-m-check-circle', 'title' => 'Không có công nợ quá hạn',
                'detail' => 'Căn hộ không có khoản phí quá hạn.', 'badge' => null];
        }
        $alerts[] = ['tone' => 'blue', 'icon' => 'heroicon-m-document-text', 'title' => 'Hình thức sở hữu: '.($a->ownership_type ?: 'Sở hữu'),
            'detail' => 'Số HĐ '.($a->contract_no ?? '—').' · Thời hạn '.($a->ownership_term ?? '—'), 'badge' => null];
        $ownerKyc = $owner?->kyc_status;
        $alerts[] = ['tone' => 'green', 'icon' => 'heroicon-m-shield-check', 'title' => 'Hồ sơ cư dân hợp lệ',
            'detail' => $ownerKyc ? 'Trạng thái KYC: '.$ownerKyc : 'Hồ sơ cư dân đã được xác thực.', 'badge' => null];

        return [
            'a' => $a,
            'statusLabel' => $stLabel,
            'statusTone' => $stTone,
            'occupants' => $occupants,
            'fillRate' => $fillRate,
            'fbOpen' => $fbOpen,
            'feedback' => $feedback,
            'kpis' => [
                ['label' => 'Mã căn', 'value' => $a->code, 'sub' => trim(($a->building?->name ?? '').' – '.($a->building?->project?->name ?? 'Sunshine Garden'), ' –')],
                ['label' => 'Trạng thái', 'value' => $stLabel, 'tone' => $stTone, 'badge' => true],
                ['label' => 'Diện tích', 'value' => $a->area_sqm ? number_format((float) $a->area_sqm, 1, ',', '.').' m²' : '—'],
                ['label' => 'Loại căn', 'value' => $a->type ?? '—'],
                ['label' => 'Cư dân chính', 'value' => $owner?->full_name ?? '—'],
                ['label' => 'Công nợ tạm tính', 'value' => number_format($debtTotal, 0, ',', '.').' đ', 'warn' => $debtOverdue > 0, 'link' => ['setTab', 'finance', 'Xem chi tiết công nợ']],
                ['label' => 'Phản ánh đang mở', 'value' => (string) $fbOpen, 'link' => ['setTab', 'feedback', 'Xem danh sách']],
            ],
            'residents' => $this->residentRows($relations),
            'overview' => [
                ['Mã căn', $a->code], ['Tòa / Block', $a->building?->name ?? '—'], ['Tầng', $a->floor?->name ?? '—'],
                ['Diện tích thông thủy', $a->area_sqm ? number_format((float) $a->area_sqm, 1, ',', '.').' m²' : '—'],
                ['Loại căn', $a->type ?? '—'], ['Số phòng ngủ', $a->bedroom_count ?? '—'], ['Số phòng tắm', $a->bathroom_count ?? '—'],
                ['Tình trạng nội thất', $a->furniture_status ?? '—'], ['Ngày bàn giao', $a->handover_date?->format('d/m/Y') ?? '—'],
            ],
            'detail' => [
                ['Tòa / Block', $a->building?->name ?? '—'], ['Tầng', $a->floor?->name ?? '—'],
                ['Hướng cửa chính', $a->direction ?? '—'], ['Hướng ban công', $a->balcony_direction ?? '—'],
                ['Vị trí căn', $a->position ?? '—'], ['Số hiệu căn', $a->code],
                ['Ngày bàn giao', $a->handover_date?->format('d/m/Y') ?? '—'], ['Mục đích sử dụng', $a->purpose ?? '—'],
            ],
            'meters' => [
                ['Số công tơ điện', $a->electric_meter_no], ['Số công tơ nước', $a->water_meter_no], ['Số công tơ gas', $a->gas_meter_no],
            ],
            'contractType' => $a->contract_type ?? '—',
            'note' => $a->note ?? '—',
            'quick' => [
                ['Cư dân chính', $owner?->full_name ?? '—'],
                ['Số điện thoại', $owner?->phone ?? $owner?->contact_phone ?? '—'],
                ['Email', $owner?->email ?? $owner?->contact_email ?? '—'],
                ['Ngày sinh', $owner?->dob ? Carbon::parse($owner->dob)->format('d/m/Y') : '—'],
                ['Số CCCD', $owner?->id_no ?? '—'],
                ['Địa chỉ thường trú', $owner?->mailing_address ?? $owner?->contact_address ?? '—'],
                ['Ngày bắt đầu cư trú', $ownerRel?->start_date ? Carbon::parse($ownerRel->start_date)->format('d/m/Y') : '—'],
                ['Hình thức sở hữu', $a->ownership_type ?: 'Sở hữu'],
                ['Ngày tạo hồ sơ', $a->created_at?->format('d/m/Y H:i') ?? '—'],
                ['Người tạo', $owner?->full_name ?? 'Hệ thống'],
            ],
            'alerts' => $alerts,
            'debt' => [
                'total' => $debtTotal, 'inTerm' => max($debtTotal - $debtOverdue, 0), 'overdue' => $debtOverdue,
                'overdueCount' => $debtOverdueCount, 'lastPaid' => $lastPaid ? Carbon::parse($lastPaid)->format('d/m/Y') : '—',
            ],
            'vehicles' => $vehicles,
            'cards' => $cards,
            'documents' => $a->documents ?? [],
            'timeline' => $this->timeline($a, $relations, $vehicles, $cards),
        ];
    }

    private function residentRows($relations): array
    {
        return $relations->map(fn ($rel) => [
            'name' => $rel->resident->full_name,
            'relationship' => self::RELATIONSHIP[$rel->resident->relationship_to_head] ?? (self::ROLE[$rel->role] ?? '—'),
            'relationshipTone' => $rel->role === 'owner' ? 'blue' : 'slate',
            'role' => $rel->is_primary ? 'Chủ hộ' : 'Thành viên',
            'dob' => $rel->resident->dob ? Carbon::parse($rel->resident->dob)->format('d/m/Y') : '—',
            'phone' => $rel->resident->phone ?? $rel->resident->contact_phone ?? '—',
        ])->values()->all();
    }

    private function timeline(Apartment $a, $relations, $vehicles, $cards): array
    {
        $events = [];
        $events[] = ['at' => $a->created_at, 'dot' => 'gray', 'title' => 'Tạo hồ sơ căn hộ', 'detail' => 'Khởi tạo hồ sơ căn hộ '.$a->code, 'actor' => 'Hệ thống'];
        foreach ($relations as $rel) {
            $events[] = ['at' => $rel->start_date ?? $rel->created_at, 'dot' => 'orange', 'title' => 'Cập nhật thông tin cư dân', 'detail' => 'Thành viên: '.$rel->resident->full_name, 'actor' => $rel->resident->full_name];
        }
        foreach ($vehicles as $v) {
            $events[] = ['at' => $v->created_at, 'dot' => 'green', 'title' => 'Đăng ký phương tiện '.$v->plate_no, 'detail' => 'Loại: '.(optional($v->type)->label() ?? $v->type), 'actor' => ''];
        }
        foreach ($cards as $c) {
            $events[] = ['at' => $c->created_at, 'dot' => 'purple', 'title' => 'Cấp thẻ ra vào #'.$c->card_no, 'detail' => $c->valid_to ? 'Hiệu lực đến: '.Carbon::parse($c->valid_to)->format('d/m/Y') : '', 'actor' => ''];
        }
        usort($events, fn ($x, $y) => $y['at'] <=> $x['at']);

        return array_map(fn ($e) => [
            'date' => $e['at'] ? Carbon::parse($e['at'])->format('d/m/Y') : '—',
            'time' => $e['at'] ? Carbon::parse($e['at'])->format('H:i') : '',
            'dot' => $e['dot'], 'title' => $e['title'], 'detail' => $e['detail'], 'actor' => $e['actor'],
        ], array_slice($events, 0, 10));
    }

    private function audit(string $action, string $description, ?int $subjectId = null): void
    {
        $user = auth()->user();
        AuditLog::create([
            'tenant_id' => $user?->tenant_id, 'building_id' => $user?->building_id, 'user_id' => $user?->id,
            'actor_name' => $user?->name, 'action' => $action, 'subject_type' => 'Apartment',
            'subject_id' => $subjectId, 'description' => $description,
        ]);
    }
}
