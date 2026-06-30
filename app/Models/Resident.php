<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Resident extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'dob' => 'date',
            'id_issued_date' => 'date',
            'join_date' => 'date',
            'documents' => 'array',
        ];
    }

    /** Avatar URL: uploaded file if present, else an initials avatar from the name. */
    protected function avatarUrl(): Attribute
    {
        return Attribute::get(function (): string {
            if ($this->avatar_path) {
                return Storage::disk('public')->url($this->avatar_path);
            }

            return 'https://ui-avatars.com/api/?background=0b1b3f&color=c8a24c&name='
                .urlencode($this->full_name ?: 'CD');
        });
    }

    public function emergencyContacts(): HasMany
    {
        return $this->hasMany(ResidentEmergencyContact::class);
    }

    public function apartments(): BelongsToMany
    {
        return $this->belongsToMany(Apartment::class, 'resident_apartment_relations')
            ->withPivot(['role', 'is_primary', 'start_date'])
            ->withTimestamps();
    }

    public function apartmentRelations(): HasMany
    {
        return $this->hasMany(ResidentApartmentRelation::class);
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    /** The global X2BMS account (person) this membership is linked to, if any. */
    public function linkedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Primary apartment relation (falls back to the first). */
    public function primaryRelation(): ?ResidentApartmentRelation
    {
        return $this->apartmentRelations->firstWhere('is_primary', true) ?? $this->apartmentRelations->first();
    }
}
