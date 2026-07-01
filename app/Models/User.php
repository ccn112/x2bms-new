<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['tenant_id', 'project_id', 'building_id', 'name', 'title', 'is_platform_admin', 'email', 'password', 'account_type', 'phone', 'id_no', 'dob', 'gender', 'nationality', 'kyc_status', 'kyc_verified_at', 'avatar_path'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    /** Role grants scoped to platform/tenant/project/building (3-tier RBAC). */
    public function roleScopes(): HasMany
    {
        return $this->hasMany(UserRoleScope::class);
    }

    /** True for self-registered resident accounts (global, not tenant-scoped). */
    public function isResident(): bool
    {
        return $this->account_type === 'resident';
    }

    /**
     * All resident memberships of this person across every tenant/company.
     * Bypasses the tenant global scope on purpose — this is the person's OWN data
     * (resident-app / platform view), never exposed to another company's BQL.
     */
    public function residentMemberships(): HasMany
    {
        return $this->hasMany(Resident::class)->withoutGlobalScope('tenant');
    }

    /** Platform-level operator — sees the whole system (Gate::before also bypasses for super_admin). */
    public function isPlatformAdmin(): bool
    {
        return (bool) $this->is_platform_admin;
    }

    /** Tenant-level operator (công ty vận hành) — sees every project in their tenant. */
    public function isTenantOperator(): bool
    {
        return $this->roleScopes()
            ->where('scope_type', UserRoleScope::SCOPE_TENANT)
            ->exists();
    }

    /**
     * Project ids this user may work in.
     * - Platform admin: all projects (null = no restriction; caller applies tenant/global view).
     * - Otherwise: explicit project-scope grants ∪ home project, within the user's tenant.
     *
     * @return array<int>|null  null = unrestricted (platform)
     */
    public function accessibleProjectIds(): ?array
    {
        if ($this->isPlatformAdmin()) {
            return null;
        }

        $ids = $this->roleScopes()
            ->where('scope_type', UserRoleScope::SCOPE_PROJECT)
            ->whereNotNull('project_id')
            ->pluck('project_id');

        if ($this->project_id) {
            $ids->push($this->project_id);
        }

        return $ids->unique()->values()->all();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // Admin panel = staff/admin accounts (the ~10k), not the 5M residents.
        return $this->is_platform_admin || $this->roles()->exists();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_platform_admin' => 'boolean',
            'dob' => 'date',
            'kyc_verified_at' => 'datetime',
        ];
    }
}
