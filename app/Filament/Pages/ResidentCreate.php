<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\ProvidesAiContext;
use App\Filament\Resources\Residents\Schemas\ResidentForm;
use App\Models\AuditLog;
use App\Models\Resident;
use App\Models\User;
use App\Support\Identity\ResidentIdentityMatcher;
use BackedEnum;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * WEB-FORM-02-01 — Thêm cư dân (themed /admin page).
 * Left: the full ResidentForm; right: Hồ sơ cư dân + Thông tin kiểm soát + X2AI rail.
 */
class ResidentCreate extends Page implements HasForms
{
    use InteractsWithForms;
    use ProvidesAiContext;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserPlus;

    protected static string|UnitEnum|null $navigationGroup = 'Cư dân & Căn hộ';

    protected static ?string $navigationLabel = 'Thêm cư dân';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'residents/create';

    protected string $view = 'filament.pages.resident-create';

    public ?array $data = [];

    /** Required fields → Vietnamese labels, for the X2AI checklist + completion bar. */
    private const REQUIRED = [
        'full_name' => 'Họ tên',
        'gender' => 'Giới tính',
        'dob' => 'Ngày sinh',
        'nationality' => 'Quốc tịch',
        'id_no' => 'CCCD/CMND',
        'id_issued_date' => 'Ngày cấp',
        'id_issued_place' => 'Nơi cấp',
        'phone' => 'SĐT đăng nhập',
        'email' => 'Email đăng nhập',
        'contact_address' => 'Địa chỉ thường trú',
        'requested_role' => 'Vai trò dự kiến',
    ];

    public function getTitle(): string
    {
        return 'Thêm cư dân';
    }

    public function mount(): void
    {
        $this->form->fill([
            'profile_status' => 'cho_bo_sung',
            'source' => 'bql_manual',
            'kyc_status' => 'unverified',
            'face_match_status' => 'not_checked',
            'requested_role' => 'owner',
            'nationality' => 'Việt Nam',
        ]);
    }

    /** Feed this screen's context into the shared floating X2AI chat (FAB). */
    protected function getViewData(): array
    {
        $acct = $this->matchedAccount();
        $this->shareAiContext([
            'title' => 'Kiểm tra hồ sơ cư dân',
            'lines' => array_map(fn (string $l): string => 'Thiếu: '.$l, $this->missingRequired()),
            'suggestions' => $acct ? [[
                'title' => 'Trùng tài khoản: '.$acct->name,
                'sub' => 'CCCD '.$acct->id_no.($acct->kyc_status === 'verified' ? ' · đã KYC' : ''),
            ]] : [],
        ]);

        return [];
    }

    public function form(Schema $schema): Schema
    {
        return ResidentForm::configure($schema)
            ->statePath('data')
            ->model(Resident::class);
    }

    protected function getForms(): array
    {
        return ['form'];
    }

    /** Missing required fields (labels) given current form state. */
    public function missingRequired(): array
    {
        return collect(self::REQUIRED)
            ->reject(fn ($label, $key) => filled($this->data[$key] ?? null))
            ->values()
            ->all();
    }

    /** Profile completion 0–100 from required fields. */
    public function completion(): int
    {
        $total = count(self::REQUIRED);
        $missing = count($this->missingRequired());

        return (int) round(($total - $missing) / $total * 100);
    }

    /**
     * Global X2BMS account matching the typed CCCD/phone — the person this
     * membership will be linked to on save. Drives the X2AI "trùng lặp" panel.
     */
    public function matchedAccount(): ?User
    {
        return app(ResidentIdentityMatcher::class)
            ->findAccount($this->data['id_no'] ?? null, $this->data['phone'] ?? null);
    }

    public function create(bool $sendForApproval = false): void
    {
        $state = $this->form->getState();
        $user = auth()->user();

        $state['tenant_id'] = $user->tenant_id;
        $state['building_id'] = $user->building_id;
        $state['code'] = 'CD-'.now()->format('ymdHis');
        $state['status'] = 'active';
        $state['profile_status'] = $sendForApproval ? 'cho_duyet' : ($state['profile_status'] ?? 'cho_bo_sung');

        // Link to the global X2BMS account by CCCD/phone, if one exists.
        $account = app(ResidentIdentityMatcher::class)->findAccount($state['id_no'] ?? null, $state['phone'] ?? null);
        if ($account) {
            $state['user_id'] = $account->id;
            $state['link_status'] = 'linked';
            $state['linked_at'] = now();
        }

        $resident = Resident::create($state);
        $this->audit($resident, $sendForApproval ? 'resident.submit' : 'resident.create',
            ($sendForApproval ? 'Gửi duyệt' : 'Tạo').' hồ sơ cư dân '.$resident->full_name
            .($account ? ' (liên kết tài khoản '.$account->name.')' : ''));

        Notification::make()
            ->title($sendForApproval ? 'Đã gửi duyệt hồ sơ cư dân' : 'Đã lưu hồ sơ cư dân')
            ->body($account ? 'Đã liên kết với tài khoản X2BMS: '.$account->name : null)
            ->success()->send();

        $this->redirect(ResidentDirectory::getUrl());
    }

    public function saveDraft(): void
    {
        $user = auth()->user();
        $payload = array_merge($this->data, [
            'tenant_id' => $user->tenant_id,
            'building_id' => $user->building_id,
            'code' => 'CD-'.now()->format('ymdHis'),
            'status' => 'pending',
            'profile_status' => 'cho_bo_sung',
            'full_name' => $this->data['full_name'] ?? 'Hồ sơ nháp',
        ]);

        $resident = Resident::create($payload);
        $this->audit($resident, 'resident.draft', 'Lưu nháp hồ sơ cư dân '.$resident->full_name);

        Notification::make()->title('Đã lưu nháp hồ sơ')->info()->send();
        $this->redirect(ResidentDirectory::getUrl());
    }

    private function audit(Resident $resident, string $action, string $description): void
    {
        $user = auth()->user();
        AuditLog::create([
            'tenant_id' => $user->tenant_id,
            'building_id' => $user->building_id,
            'user_id' => $user->id,
            'actor_name' => $user->name,
            'action' => $action,
            'subject_type' => Resident::class,
            'subject_id' => $resident->id,
            'description' => $description,
        ]);
    }
}
