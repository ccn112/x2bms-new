<?php

namespace App\Filament\Pages;

use App\Models\Resident;
use App\Support\Context\CurrentContext;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;

/**
 * BQL-01-10 — Chất lượng dữ liệu cư dân (Resident Data Quality Dashboard).
 * Surfaces completeness / duplicate / verification gaps across the project's residents
 * so BQL can clean the master data. All figures are computed live from `residents`.
 */
class ResidentDataQuality extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static string|\UnitEnum|null $navigationGroup = 'Cư dân & Căn hộ';

    protected static ?string $navigationLabel = 'Chất lượng dữ liệu';

    protected static ?int $navigationSort = 12;

    protected static ?string $title = 'Chất lượng dữ liệu cư dân';

    protected static ?string $slug = 'residents/data-quality';

    protected string $view = 'filament.pages.resident-data-quality';

    /** @return Builder<Resident> */
    private function scoped(): Builder
    {
        return Resident::query()->whereIn('building_id', app(CurrentContext::class)->buildingIds() ?: [0]);
    }

    protected function getViewData(): array
    {
        $total = (clone $this->scoped())->count();
        $missingPhone = (clone $this->scoped())->where(fn (Builder $q) => $q->whereNull('phone')->orWhere('phone', ''))->count();
        $missingEmail = (clone $this->scoped())->where(fn (Builder $q) => $q->whereNull('email')->orWhere('email', ''))->count();
        $missingId = (clone $this->scoped())->where(fn (Builder $q) => $q->whereNull('id_no')->orWhere('id_no', ''))->count();
        $missingDob = (clone $this->scoped())->whereNull('dob')->count();
        $kycPending = (clone $this->scoped())->where(fn (Builder $q) => $q->whereNull('kyc_status')->orWhereNotIn('kyc_status', ['verified', 'approved', 'passed']))->count();
        $unlinked = (clone $this->scoped())->where(fn (Builder $q) => $q->whereNull('user_id')->orWhere('link_status', '!=', 'linked'))->count();

        // Duplicate candidates — same non-empty phone / email within the project scope.
        $dupPhones = (clone $this->scoped())->whereNotNull('phone')->where('phone', '!=', '')
            ->selectRaw('phone, COUNT(*) as c')->groupBy('phone')->havingRaw('COUNT(*) > 1')->pluck('c', 'phone');
        $dupEmails = (clone $this->scoped())->whereNotNull('email')->where('email', '!=', '')
            ->selectRaw('email, COUNT(*) as c')->groupBy('email')->havingRaw('COUNT(*) > 1')->pluck('c', 'email');
        $dupResidents = $dupPhones->sum() + $dupEmails->sum();

        // Fully complete = phone + email + id_no + dob present.
        $complete = (clone $this->scoped())
            ->whereNotNull('phone')->where('phone', '!=', '')
            ->whereNotNull('email')->where('email', '!=', '')
            ->whereNotNull('id_no')->where('id_no', '!=', '')
            ->whereNotNull('dob')->count();
        $completeness = $total ? round($complete / $total * 100) : 0;

        // Rows needing attention (top 40) with per-record issue tags.
        $issues = (clone $this->scoped())->latest('updated_at')->limit(200)->get()
            ->map(function (Resident $r) use ($dupPhones, $dupEmails) {
                $tags = [];
                if (! $r->phone) $tags[] = 'Thiếu SĐT';
                if (! $r->email) $tags[] = 'Thiếu email';
                if (! $r->id_no) $tags[] = 'Thiếu CCCD';
                if (! $r->dob) $tags[] = 'Thiếu ngày sinh';
                if ($r->kyc_status && ! in_array($r->kyc_status, ['verified', 'approved', 'passed'])) $tags[] = 'KYC chờ';
                if ($r->phone && ($dupPhones[$r->phone] ?? 0) > 1) $tags[] = 'Trùng SĐT';
                if ($r->email && ($dupEmails[$r->email] ?? 0) > 1) $tags[] = 'Trùng email';

                return ['id' => $r->id, 'name' => $r->full_name, 'phone' => $r->phone, 'email' => $r->email, 'code' => $r->code, 'tags' => $tags];
            })
            ->filter(fn ($r) => count($r['tags']) > 0)
            ->take(40)->values()->all();

        return [
            'kpis' => [
                ['label' => 'Tổng cư dân', 'value' => number_format($total), 'accent' => 'blue', 'sub' => 'Trong phạm vi dự án'],
                ['label' => 'Điểm hoàn thiện', 'value' => $completeness.'%', 'accent' => $completeness >= 80 ? 'green' : 'amber', 'sub' => $complete.'/'.$total.' đầy đủ'],
                ['label' => 'Thiếu SĐT', 'value' => number_format($missingPhone), 'accent' => 'amber'],
                ['label' => 'Thiếu email', 'value' => number_format($missingEmail), 'accent' => 'amber'],
                ['label' => 'Thiếu CCCD', 'value' => number_format($missingId), 'accent' => 'red'],
                ['label' => 'Trùng lặp', 'value' => number_format($dupResidents), 'accent' => 'red', 'sub' => 'Ứng viên trùng SĐT/email'],
            ],
            'breakdown' => [
                ['label' => 'Thiếu ngày sinh', 'value' => $missingDob, 'total' => $total, 'color' => 'amber'],
                ['label' => 'KYC chưa xác thực', 'value' => $kycPending, 'total' => $total, 'color' => 'amber'],
                ['label' => 'Chưa liên kết tài khoản', 'value' => $unlinked, 'total' => $total, 'color' => 'blue'],
                ['label' => 'Thiếu CCCD', 'value' => $missingId, 'total' => $total, 'color' => 'red'],
                ['label' => 'Thiếu SĐT', 'value' => $missingPhone, 'total' => $total, 'color' => 'amber'],
                ['label' => 'Thiếu email', 'value' => $missingEmail, 'total' => $total, 'color' => 'amber'],
            ],
            'issues' => $issues,
        ];
    }
}
