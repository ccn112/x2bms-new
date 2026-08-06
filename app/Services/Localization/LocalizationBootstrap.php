<?php

declare(strict_types=1);

namespace App\Services\Localization;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Builds the localization block returned by /me/bootstrap, /public/bootstrap and the
 * standalone /localization/bootstrap endpoint (docs 06_API_CONTRACT LocalizationBootstrap).
 *
 * Shape (snake_case to match the rest of the mobile bootstrap payload):
 *   current_locale, device_locale, fallback_locale, follow_device,
 *   auto_translate_content, supported_locales[{code,name,native_name,direction}],
 *   pack_versions{namespace: version}
 *
 * The block is purely informational for the resident app, whose UI strings come from
 * bundled ARB; it drives which locale the app selects and whether auto-translate is on.
 */
final class LocalizationBootstrap
{
    public function __construct(private readonly LocaleResolver $resolver)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(
        Request $request,
        ?int $userId = null,
        ?int $tenantId = null,
        ?int $projectId = null,
    ): array {
        $supportedCodes = $this->resolver->supportedLocales($tenantId, $projectId);

        $preference = $userId === null
            ? null
            : DB::table('user_locale_preferences')->where('user_id', $userId)->first();

        $currentLocale = $this->resolver->resolve($request, $userId, $tenantId, $projectId);

        return [
            'current_locale' => $currentLocale,
            'device_locale' => $request->header((string) config('localization.device_header', 'X-Device-Locale')),
            'fallback_locale' => (string) config('localization.fallback_locale', 'vi-VN'),
            'follow_device' => $preference === null ? true : (bool) $preference->follow_device,
            'auto_translate_content' => $preference === null ? false : (bool) $preference->auto_translate_content,
            'supported_locales' => $this->supportedLocaleDetails($supportedCodes->all()),
            'pack_versions' => $this->packVersions($currentLocale),
        ];
    }

    /**
     * @param array<int, string> $codes
     * @return array<int, array<string, string>>
     */
    private function supportedLocaleDetails(array $codes): array
    {
        if ($codes === []) {
            return [];
        }

        $rows = DB::table('locales')
            ->whereIn('code', $codes)
            ->get(['code', 'name', 'native_name', 'direction'])
            ->keyBy('code');

        // Preserve the resolver's ordering (sort_order / tenant preference order).
        return collect($codes)
            ->map(function (string $code) use ($rows): ?array {
                $row = $rows->get($code);

                if ($row === null) {
                    return null;
                }

                return [
                    'code' => (string) $row->code,
                    'name' => (string) $row->name,
                    'native_name' => (string) $row->native_name,
                    'direction' => (string) $row->direction,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Latest published seed/release version per namespace for the current locale, so the
     * app can decide whether to fetch a remote pack (I18N-008/009). Cheap + cached.
     *
     * @return array<string, string>
     */
    private function packVersions(string $locale): array
    {
        return Cache::remember(
            "i18n:pack_versions:{$locale}",
            300,
            static function () use ($locale): array {
                return DB::table('translation_releases as r')
                    ->join('translation_namespaces as n', 'n.id', '=', 'r.namespace_id')
                    ->where('r.locale', $locale)
                    ->where('r.status', 'published')
                    ->where('r.scope_type', 'product')
                    ->where('r.scope_id', '')
                    ->orderBy('r.published_at')
                    ->pluck('r.version', 'n.code')
                    ->all();
            },
        );
    }
}
