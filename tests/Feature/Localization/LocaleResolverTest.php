<?php

declare(strict_types=1);

namespace Tests\Feature\Localization;

use App\Services\Localization\LocaleResolver;
use Database\Seeders\LocalizationMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class LocaleResolverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LocalizationMasterSeeder::class);
    }

    public function test_explicit_supported_locale_wins(): void
    {
        // Represent a client that sent no Accept-Language (Symfony's Request::create
        // otherwise injects a default "en-us,en;q=0.5" that would jump the resolution chain).
        $request = Request::create('/api/v1/bootstrap', 'GET', server: ['HTTP_ACCEPT_LANGUAGE' => '']);
        $request->headers->set('X-Device-Locale', 'vi-VN');

        $resolved = app(LocaleResolver::class)->resolve(
            request: $request,
            explicitLocale: 'en-US',
        );

        self::assertSame('en-US', $resolved);
    }

    public function test_unsupported_device_locale_falls_back_to_vietnamese(): void
    {
        // Represent a client that sent no Accept-Language (Symfony's Request::create
        // otherwise injects a default "en-us,en;q=0.5" that would jump the resolution chain).
        $request = Request::create('/api/v1/bootstrap', 'GET', server: ['HTTP_ACCEPT_LANGUAGE' => '']);
        $request->headers->set('X-Device-Locale', 'fr-FR');

        self::assertSame('vi-VN', app(LocaleResolver::class)->resolve($request));
    }

    public function test_tenant_supported_locales_restrict_resolution(): void
    {
        DB::table('tenant_locale_settings')->insert([
            'tenant_id' => 99,
            'default_locale' => 'vi-VN',
            'supported_locales' => json_encode(['vi-VN']),
            'allow_auto_translate' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Represent a client that sent no Accept-Language (Symfony's Request::create
        // otherwise injects a default "en-us,en;q=0.5" that would jump the resolution chain).
        $request = Request::create('/api/v1/bootstrap', 'GET', server: ['HTTP_ACCEPT_LANGUAGE' => '']);
        $request->headers->set('X-Device-Locale', 'en-US');

        self::assertSame('vi-VN', app(LocaleResolver::class)->resolve(
            request: $request,
            tenantId: 99,
        ));
    }
}
