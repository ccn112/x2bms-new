<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/** Chủ đầu tư dự án — entity dùng chung, dedup theo slug. */
class Developer extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = ['metadata_json' => 'array'];

    public function publicProjects(): HasMany
    {
        return $this->hasMany(PublicProject::class);
    }

    /**
     * Upsert chủ đầu tư theo slug (dedup nhiều dự án cùng CĐT → 1 record).
     * Trả về Developer hoặc null nếu tên rỗng.
     */
    public static function upsertByName(?string $name, array $extra = []): ?self
    {
        $name = trim(preg_replace('/\s+/u', ' ', (string) $name));
        if ($name === '') {
            return null;
        }
        $slug = Str::slug($name);
        if ($slug === '') {
            $slug = Str::lower(md5($name));
        }

        $dev = static::withTrashed()->firstOrNew(['slug' => $slug]);
        if ($dev->trashed()) {
            $dev->restore();
        }
        $dev->name = $name;
        foreach (['website', 'logo_path', 'description', 'code', 'source'] as $k) {
            if (! empty($extra[$k]) && empty($dev->{$k})) {
                $dev->{$k} = $extra[$k];
            }
        }
        if (! empty($extra['metadata_json'])) {
            $dev->metadata_json = array_merge((array) $dev->metadata_json, $extra['metadata_json']);
        }
        $dev->save();

        return $dev;
    }
}
