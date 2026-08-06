<?php

declare(strict_types=1);

namespace Tests\Unit\Localization;

use App\Services\Localization\TranslationKeyKind;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Locks the key-classification taxonomy shown in the Translation Center. */
final class TranslationKeyKindTest extends TestCase
{
    #[DataProvider('cases')]
    public function test_classify(?string $category, string $key, string $expected): void
    {
        self::assertSame($expected, TranslationKeyKind::classify($category, $key));
    }

    public static function cases(): array
    {
        return [
            'nav from category' => ['navigation', 'navigation.home', TranslationKeyKind::NAV],
            'title by suffix' => ['billing', 'billing.title', TranslationKeyKind::TITLE],
            'action verb' => ['payment', 'payment.pay_now', TranslationKeyKind::ACTION],
            'action category' => ['action', 'action.approve', TranslationKeyKind::ACTION],
            'status category' => ['status', 'status.overdue', TranslationKeyKind::STATUS],
            'error category' => ['error', 'error.network', TranslationKeyKind::ERROR],
            'api is error' => ['api', 'api.invalid_locale', TranslationKeyKind::ERROR],
            'notification category' => ['notifications', 'notifications.emergency', TranslationKeyKind::NOTIFICATION],
            'helper by hint' => ['settings', 'settings.auto_translate_description', TranslationKeyKind::HELPER],
            'plain label' => ['home', 'home.greeting_morning', TranslationKeyKind::LABEL],
            'sign_in is action' => ['auth', 'auth.sign_in', TranslationKeyKind::ACTION],
        ];
    }

    public function test_meta_and_options_cover_every_kind(): void
    {
        $options = TranslationKeyKind::options();
        self::assertArrayHasKey(TranslationKeyKind::NAV, $options);
        self::assertSame('Menu / Điều hướng', $options[TranslationKeyKind::NAV]);
        self::assertSame('Thông báo', TranslationKeyKind::meta(TranslationKeyKind::NOTIFICATION)['label']);
        self::assertSame('danger', TranslationKeyKind::meta(TranslationKeyKind::ERROR)['color']);
    }
}
