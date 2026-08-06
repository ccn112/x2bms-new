<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Localization\LocaleResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Resolves the request locale (device header / Accept-Language / user preference, gated
 * to enabled + tenant-supported locales) and applies it for server-rendered strings.
 *
 * API talks BCP-47 (vi-VN, en-US); Laravel's translator uses the short code (vi, en) that
 * matches lang/{vi,en}. We map internally and echo Content-Language so clients and caches
 * can vary on it. Defensive: any resolution failure leaves the configured default in place.
 */
final class SetLocaleFromRequest
{
    public function __construct(private readonly LocaleResolver $resolver)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $bcp47 = $this->resolveSafely($request);

        if ($bcp47 !== null) {
            App::setLocale($this->toInternal($bcp47));
            $request->attributes->set('locale', $bcp47);
        }

        $response = $next($request);

        if ($bcp47 !== null && ! $response->headers->has('Content-Language')) {
            $response->headers->set('Content-Language', $bcp47);
        }

        return $response;
    }

    private function resolveSafely(Request $request): ?string
    {
        try {
            $userId = $request->user()?->getAuthIdentifier();
            $tenantId = $request->attributes->get('tenant_id');
            $projectId = $request->attributes->get('project_id');

            return $this->resolver->resolve(
                $request,
                is_numeric($userId) ? (int) $userId : null,
                is_numeric($tenantId) ? (int) $tenantId : null,
                is_numeric($projectId) ? (int) $projectId : null,
            );
        } catch (Throwable) {
            // Localization tables not ready or resolver failure: keep configured default.
            return null;
        }
    }

    private function toInternal(string $bcp47): string
    {
        return strtolower(explode('-', $bcp47, 2)[0]);
    }
}
