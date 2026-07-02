<?php

namespace App\Filament\Pages;

use App\Models\AuditLog;
use App\Support\Context\CurrentContext;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Validator;

/**
 * WEB-UX-02A — Hồ sơ của tôi (My Profile).
 * Summary card + editable personal/contact info + read-only role + notification
 * preferences. Reached from the header avatar menu. Saves to the current user + audit.
 */
class MyProfile extends Page
{
    protected static ?string $slug = 'my-profile';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Hồ sơ của tôi';

    protected string $view = 'filament.pages.my-profile';

    public string $name = '';

    public string $phone = '';

    public string $email = '';

    public string $title_field = '';

    /** @var array<string,array<string,bool>> */
    public array $notif = [];

    public const NOTIF_ROWS = [
        'approval' => 'Phê duyệt',
        'feedback' => 'Phản ánh & Yêu cầu',
        'finance' => 'Công nợ & Thu phí',
        'technical' => 'Cảnh báo kỹ thuật',
    ];

    public const NOTIF_CHANNELS = ['app' => 'Ứng dụng', 'email' => 'Email', 'sms' => 'SMS', 'zalo' => 'Zalo'];

    public function mount(): void
    {
        $u = auth()->user();
        $this->name = $u->name ?? '';
        $this->phone = $u->phone ?? '';
        $this->email = $u->email ?? '';
        $this->title_field = $u->title ?? '';
        $this->notif = session('x2_notif_prefs', $this->defaultNotif());
    }

    private function defaultNotif(): array
    {
        $rows = [];
        foreach (array_keys(self::NOTIF_ROWS) as $r) {
            $rows[$r] = ['app' => true, 'email' => true, 'sms' => $r === 'finance', 'zalo' => $r !== 'finance'];
        }

        return $rows;
    }

    public function save(): void
    {
        $data = Validator::make(
            ['name' => $this->name, 'phone' => $this->phone, 'email' => $this->email],
            [
                'name' => ['required', 'string', 'max:120'],
                'phone' => ['nullable', 'string', 'max:20'],
                'email' => ['required', 'email', 'max:150', 'unique:users,email,'.auth()->id()],
            ],
            [],
            ['name' => 'Họ và tên', 'phone' => 'Số điện thoại', 'email' => 'Email']
        )->validate();

        $u = auth()->user();
        $u->update($data);
        session(['x2_notif_prefs' => $this->notif]);

        AuditLog::create([
            'tenant_id' => $u->tenant_id, 'building_id' => $u->building_id,
            'user_id' => $u->id, 'actor_name' => $u->name,
            'action' => 'profile.update', 'description' => 'Cập nhật hồ sơ cá nhân',
        ]);

        Notification::make()->title('Đã lưu hồ sơ')->success()->send();
    }

    protected function getViewData(): array
    {
        $u = auth()->user();
        $ctx = app(CurrentContext::class);
        $roles = method_exists($u, 'getRoleNames') ? $u->getRoleNames()->all() : [];

        return [
            'user' => $u,
            'roles' => $roles ?: [$ctx->workspaceLabel()],
            'project' => $ctx->project(),
            'buildingLabel' => $ctx->buildings()->count() === 1 ? $ctx->buildings()->first()?->name : $ctx->buildings()->count().' tòa',
        ];
    }
}
