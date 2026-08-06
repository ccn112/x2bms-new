<?php

declare(strict_types=1);

namespace Tests\Feature\Localization;

use App\Models\User;
use Database\Seeders\LocalizationMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * I18N-003/004 — locale preference API + localization bootstrap block.
 *
 * Covers: bootstrap exposes enabled/supported locales, resolution defaults to vi-VN,
 * explicit preference persists + is reflected back, unsupported locales are rejected,
 * and unauthenticated writes are blocked.
 */
final class LocalePreferenceApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LocalizationMasterSeeder::class);
    }

    public function test_public_bootstrap_exposes_localization_block(): void
    {
        $response = $this->getJson('/api/v1/public/bootstrap')->assertOk();

        $block = $response->json('data.localization');
        self::assertSame('vi-VN', $block['current_locale']);
        self::assertSame('vi-VN', $block['fallback_locale']);
        self::assertTrue($block['follow_device']);
        self::assertFalse($block['auto_translate_content']);

        $codes = array_column($block['supported_locales'], 'code');
        self::assertContains('vi-VN', $codes);
        self::assertContains('en-US', $codes);
        self::assertNotContains('ko-KR', $codes, 'disabled locales must not be offered');

        $vi = collect($block['supported_locales'])->firstWhere('code', 'vi-VN');
        self::assertSame('Tiếng Việt', $vi['native_name']);
        self::assertSame('ltr', $vi['direction']);
    }

    public function test_content_language_header_follows_device_locale(): void
    {
        $this->getJson('/api/v1/public/bootstrap', ['X-Device-Locale' => 'en-US'])
            ->assertOk()
            ->assertHeader('Content-Language', 'en-US');
    }

    public function test_me_bootstrap_reflects_updated_preference(): void
    {
        $user = User::create([
            'name' => 'Locale User',
            'email' => 'locale.user@test.vn',
            'password' => bcrypt('secret'),
        ]);
        Sanctum::actingAs($user, ['resident']);

        // Default before any preference is stored.
        $this->getJson('/api/v1/me/bootstrap')
            ->assertOk()
            ->assertJsonPath('data.localization.current_locale', 'vi-VN')
            ->assertJsonPath('data.localization.follow_device', true)
            ->assertJsonPath('data.localization.auto_translate_content', false);

        // Explicit switch to English + auto-translate on.
        $this->patchJson('/api/v1/me/localization-preference', [
            'locale' => 'en-US',
            'follow_device' => false,
            'auto_translate_content' => true,
        ])->assertOk()->assertJsonPath('data.locale', 'en-US');

        $this->assertDatabaseHas('user_locale_preferences', [
            'user_id' => $user->id,
            'locale' => 'en-US',
            'follow_device' => false,
            'auto_translate_content' => true,
        ]);

        // Bootstrap now resolves the stored explicit preference.
        $this->getJson('/api/v1/me/bootstrap')
            ->assertOk()
            ->assertJsonPath('data.localization.current_locale', 'en-US')
            ->assertJsonPath('data.localization.follow_device', false)
            ->assertJsonPath('data.localization.auto_translate_content', true);
    }

    public function test_unsupported_locale_is_rejected(): void
    {
        $user = User::create([
            'name' => 'Bad Locale',
            'email' => 'bad.locale@test.vn',
            'password' => bcrypt('secret'),
        ]);
        Sanctum::actingAs($user, ['resident']);

        $this->patchJson('/api/v1/me/localization-preference', [
            'locale' => 'fr-FR',
            'follow_device' => false,
            'auto_translate_content' => false,
        ])->assertStatus(422);

        $this->assertDatabaseMissing('user_locale_preferences', ['user_id' => $user->id]);
    }

    public function test_unauthenticated_preference_update_is_blocked(): void
    {
        $this->patchJson('/api/v1/me/localization-preference', [
            'locale' => 'en-US',
            'follow_device' => false,
            'auto_translate_content' => false,
        ])->assertStatus(401);
    }
}
