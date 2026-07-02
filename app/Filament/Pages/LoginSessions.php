<?php

namespace App\Filament\Pages;

use App\Models\AuditLog;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * WEB-UX-02C — Phiên đăng nhập (Login Sessions).
 * Lists the current user's sessions from the database `sessions` table with device /
 * browser / IP / last-activity, and allows revoking a single session or all others.
 * The current session is marked and cannot be revoked here. Actions write audit.
 */
class LoginSessions extends Page
{
    protected static ?string $slug = 'sessions';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Phiên đăng nhập';

    protected string $view = 'filament.pages.login-sessions';

    public function revoke(string $id): void
    {
        if ($id === session()->getId()) {
            Notification::make()->title('Không thể thu hồi phiên hiện tại')->warning()->send();

            return;
        }
        DB::table('sessions')->where('id', $id)->where('user_id', auth()->id())->delete();
        $this->audit('session.revoke', 'Thu hồi một phiên đăng nhập');
        Notification::make()->title('Đã thu hồi phiên')->success()->send();
    }

    public function revokeOthers(): void
    {
        $n = DB::table('sessions')->where('user_id', auth()->id())->where('id', '!=', session()->getId())->delete();
        $this->audit('session.revoke_others', 'Đăng xuất khỏi '.$n.' thiết bị khác');
        Notification::make()->title('Đã đăng xuất '.$n.' thiết bị khác')->success()->send();
    }

    protected function getViewData(): array
    {
        $currentId = session()->getId();
        $rows = DB::table('sessions')->where('user_id', auth()->id())->orderByDesc('last_activity')->get()
            ->map(function ($s) use ($currentId) {
                [$device, $browser] = $this->parseAgent($s->user_agent ?? '');

                return [
                    'id' => $s->id,
                    'device' => $device,
                    'browser' => $browser,
                    'ip' => $s->ip_address ?: '—',
                    'last' => $s->last_activity ? Carbon::createFromTimestamp($s->last_activity)->diffForHumans() : '—',
                    'current' => $s->id === $currentId,
                ];
            });

        return [
            'current' => $rows->firstWhere('current', true),
            'sessions' => $rows,
            'otherCount' => $rows->where('current', false)->count(),
        ];
    }

    /** @return array{0:string,1:string} */
    private function parseAgent(string $ua): array
    {
        $device = match (true) {
            str_contains($ua, 'iPhone') => 'iPhone',
            str_contains($ua, 'iPad') => 'iPad',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'Macintosh') => 'macOS',
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Linux') => 'Linux',
            default => 'Thiết bị khác',
        };
        $browser = match (true) {
            str_contains($ua, 'Edg') => 'Edge',
            str_contains($ua, 'Chrome') => 'Chrome',
            str_contains($ua, 'Firefox') => 'Firefox',
            str_contains($ua, 'Safari') => 'Safari',
            default => 'Trình duyệt khác',
        };

        return [$device, $browser];
    }

    private function audit(string $action, string $desc): void
    {
        $u = auth()->user();
        AuditLog::create([
            'tenant_id' => $u->tenant_id, 'building_id' => $u->building_id,
            'user_id' => $u->id, 'actor_name' => $u->name, 'action' => $action, 'description' => $desc,
        ]);
    }
}
