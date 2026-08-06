<?php

declare(strict_types=1);

namespace Tests\Feature\Localization;

use App\Filament\Sa\Resources\Locales\Pages\ListLocales;
use App\Filament\Sa\Resources\TranslationKeys\Pages\ListTranslationKeys;
use App\Filament\Sa\Resources\TranslationReleases\Pages\ListTranslationReleases;
use App\Models\User;
use Database\Seeders\LocalizationMasterSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Renders the Translation Center pages as a platform admin so the full Filament table
 * build (columns incl. inline TextInputColumn, kind/category badges, groups, filters,
 * the "Phát hành gói" header action) is exercised server-side — a plain 302 route check
 * never reaches the table builder.
 */
final class TranslationCenterPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LocalizationMasterSeeder::class);

        $admin = User::create([
            'name' => 'Platform Admin',
            'email' => 'sa.i18n@test.vn',
            'password' => bcrypt('secret'),
            'account_type' => 'staff',
            'is_platform_admin' => true,
        ]);
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('sa'));
    }

    public function test_translation_keys_page_renders_with_classification_and_publish_action(): void
    {
        Livewire::test(ListTranslationKeys::class)
            ->assertSuccessful()
            ->assertTableColumnExists('kind')        // classification badge column
            ->assertTableColumnExists('current_vi')  // inline-edit value column
            ->assertActionExists('publishPack');     // the "Phát hành gói" header action
    }

    public function test_releases_and_locales_pages_render(): void
    {
        Livewire::test(ListTranslationReleases::class)->assertSuccessful();
        Livewire::test(ListLocales::class)->assertSuccessful();
    }
}
