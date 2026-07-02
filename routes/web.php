<?php

use App\Models\AuditLog;
use App\Models\Project;
use App\Support\Context\CurrentContext;
use Illuminate\Support\Facades\Route;

// Designed UI lives on the themed Filament panel at /admin; stock CRUD at /fila.
Route::get('/', fn () => redirect('/admin'));

Route::middleware(['auth'])->group(function () {
    // WEB-UX-01 — switch the active project context, then return to the page.
    Route::get('/context/project/{project}', function (Project $project) {
        app(CurrentContext::class)->setProject($project->id);

        $user = auth()->user();
        AuditLog::create([
            'tenant_id' => $user->tenant_id,
            'building_id' => $user->building_id,
            'user_id' => $user->id,
            'actor_name' => $user->name,
            'action' => 'context.switch_project',
            'description' => 'Chuyển ngữ cảnh sang dự án: '.$project->name,
        ]);

        return back();
    })->name('context.project');

    // WEB-UX-03 — switch the active workspace (BQL / HQ / SuperAdmin), then return.
    Route::get('/context/workspace/{key}', function (string $key) {
        $ctx = app(CurrentContext::class);
        $ctx->setWorkspace($key);

        $user = auth()->user();
        AuditLog::create([
            'tenant_id' => $user->tenant_id,
            'building_id' => $user->building_id,
            'user_id' => $user->id,
            'actor_name' => $user->name,
            'action' => 'context.switch_workspace',
            'description' => 'Chuyển workspace sang: '.($ctx->workspaceLabel()),
        ]);

        return back();
    })->whereIn('key', ['bql', 'hq', 'superadmin'])->name('context.workspace');

    // HQ Portal — set the multi-project aggregation scope (empty = all projects).
    Route::post('/context/hq-projects', function (\Illuminate\Http\Request $request) {
        $ids = array_map('intval', (array) $request->input('project_ids', []));
        app(CurrentContext::class)->setHqProjects($ids);

        return back();
    })->name('context.hq_projects');

    // HQ Portal — platform admin switches the company (tenant) they operate as.
    Route::get('/context/hq-tenant/{tenant}', function (\App\Models\Tenant $tenant) {
        $user = auth()->user();
        abort_unless($user->isPlatformAdmin(), 403);

        session([
            'hq_tenant_id' => $tenant->id,
            'hq_selected_project_ids' => [], // reset project scope for the new company
        ]);

        return back();
    })->name('context.hq_tenant');
});

// Legacy standalone routes (pre-Filament-unification) now resolve inside /admin.
Route::redirect('/dashboard', '/admin/dashboard');
Route::redirect('/residents', '/admin/residents');
Route::redirect('/apartments', '/admin/apartments');
Route::redirect('/vehicles-cards', '/admin/vehicles-cards');
Route::redirect('/resident-approvals', '/admin/resident-approvals');
