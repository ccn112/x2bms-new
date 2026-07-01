<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Batch 07 — API billing chỉ cho SuperAdmin/Billing admin (is_platform_admin). */
class EnsurePlatformAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user() ?? auth()->user();

        if (! $user || ! $user->isPlatformAdmin()) {
            abort(403, 'Chỉ SuperAdmin/Billing admin được truy cập API billing.');
        }

        return $next($request);
    }
}
