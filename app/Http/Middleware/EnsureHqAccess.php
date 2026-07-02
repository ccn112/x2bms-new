<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Panel guard cho /hq — chỉ cho phép platform admin hoặc tenant operator (HQ)
 * vào Cổng Công ty. BQL/cư dân bị chặn 403 ngay ở tầng panel.
 */
class EnsureHqAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless(
            $user && ($user->isPlatformAdmin() || $user->isTenantOperator()),
            403,
            'Bạn không có quyền truy cập Cổng Công ty (HQ).',
        );

        return $next($request);
    }
}
