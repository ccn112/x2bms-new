<?php

namespace App\Models\Concerns;

use App\Support\Context\CurrentContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * Project-tier (workspace) row-level scope, layered on top of {@see BelongsToTenant}.
 *
 * The management-board (BQL) workspace is the project. Most business tables scope to
 * a project through `building_id` (a project owns many buildings); a few carry an
 * explicit `project_id`. This trait auto-detects the column and adds a global scope
 * that restricts rows to the projects the current user may access.
 *
 * No-op (leaves the query unscoped) when:
 *   - running in console (migrations/seeders/queue/tests via artisan),
 *   - the user is a platform admin (sees everything),
 *   - the user is a tenant operator / HQ (sees every project in their tenant — the
 *     tenant scope already draws that boundary),
 *   - there is no authenticated user.
 *
 * Project-level staff are restricted to `accessibleProjectIds()`. Bypass anywhere with
 * `Model::withoutGlobalScope('project')` (e.g. cross-project aggregation dashboards),
 * exactly like the tenant scope's `withoutGlobalScope('tenant')`.
 */
trait BelongsToProject
{
    /** @var array<string, string|null> table => scope column (project_id|building_id|null) */
    protected static array $projectScopeColumnCache = [];

    public static function bootBelongsToProject(): void
    {
        static::addGlobalScope('project', function (Builder $builder) {
            $projectIds = static::currentProjectIds();
            if ($projectIds === null) {
                return; // platform admin / HQ operator / console / guest → unscoped
            }

            $model = $builder->getModel();
            $column = static::projectScopeColumn($model);
            $table = $model->getTable();

            if ($column === 'project_id') {
                $builder->whereIn($table.'.project_id', $projectIds);
            } elseif ($column === 'building_id') {
                // project_id lives on `buildings`; resolve via a sub-select (no N+1).
                $builder->whereIn($table.'.building_id', function ($q) use ($projectIds) {
                    $q->select('id')->from('buildings')->whereIn('project_id', $projectIds);
                });
            }
        });

        static::creating(function (Model $model) {
            // Only auto-fill the explicit project_id column; building_id is set by callers.
            if (static::projectScopeColumn($model) !== 'project_id' || ! empty($model->project_id)) {
                return;
            }

            if (! app()->runningInConsole()) {
                $projectId = app(CurrentContext::class)->projectId();
                if ($projectId !== null) {
                    $model->project_id = $projectId;
                }
            }
        });
    }

    /**
     * Projects the current request is limited to, or null to leave queries unscoped.
     *
     * @return array<int>|null
     */
    protected static function currentProjectIds(): ?array
    {
        if (app()->runningInConsole()) {
            return null;
        }

        $user = auth()->user();
        if (! $user) {
            return null;
        }

        // Platform admin sees all; tenant operator sees all projects in their tenant
        // (the tenant scope already bounds that) → both leave the project scope open.
        if ($user->isPlatformAdmin() || $user->isTenantOperator()) {
            return null;
        }

        // accessibleProjectIds() returns null only for platform admins (handled above),
        // so an empty array here means "granted no project" → see nothing.
        return $user->accessibleProjectIds() ?? [];
    }

    protected static function projectScopeColumn(Model $model): ?string
    {
        $table = $model->getTable();

        if (! array_key_exists($table, static::$projectScopeColumnCache)) {
            $columns = Schema::getColumnListing($table);
            static::$projectScopeColumnCache[$table] = in_array('project_id', $columns, true)
                ? 'project_id'
                : (in_array('building_id', $columns, true) ? 'building_id' : null);
        }

        return static::$projectScopeColumnCache[$table];
    }
}
