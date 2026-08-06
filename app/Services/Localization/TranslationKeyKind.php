<?php

declare(strict_types=1);

namespace App\Services\Localization;

/**
 * Classifies a translation key into a UI "kind" so the Translation Center can tell an
 * editor what each string is: a menu/nav item, a screen/section title, a button/action,
 * a status chip, a notification, an error/system message, a helper note, or a plain label.
 *
 * Deterministic: derived from the seed `category` + the dotted `key`, so re-seeding always
 * yields the same classification (no manual tagging to maintain).
 */
final class TranslationKeyKind
{
    public const NAV = 'nav';
    public const TITLE = 'title';
    public const ACTION = 'action';
    public const STATUS = 'status';
    public const NOTIFICATION = 'notification';
    public const ERROR = 'error';
    public const HELPER = 'helper';
    public const LABEL = 'label';

    /** Common action verbs that appear as `common.*` / trailing key segments. */
    private const ACTION_WORDS = [
        'save', 'cancel', 'submit', 'confirm', 'delete', 'edit', 'apply', 'retry',
        'close', 'continue', 'next', 'back', 'book', 'pay_now', 'claim', 'send',
        'verify', 'create', 'sign_in', 'sign_out', 'resend_otp', 'send_otp',
        'export', 'import', 'download', 'upload', 'refresh', 'restore', 'archive',
        'translate', 'view_all', 'view_detail', 'mark_all_read', 'share_pass',
        'switch_apartment', 'add_photo', 'reply', 'comment',
    ];

    /** Suffixes/segments that read as secondary/helper text. */
    private const HELPER_HINTS = [
        'subtitle', 'description', 'disclaimer', 'caveat', 'hint', 'help',
        'note', 'warning', 'placeholder', 'only_published_visible',
    ];

    public static function classify(?string $category, string $key): string
    {
        $category = strtolower(trim((string) $category));
        $last = self::lastSegment($key);

        // 1. Category-driven kinds (strongest signal).
        $byCategory = match ($category) {
            'navigation' => self::NAV,
            'status' => self::STATUS,
            'error', 'api', 'offline' => self::ERROR,
            'notifications', 'delivery', 'template' => self::NOTIFICATION,
            'action' => self::ACTION,
            default => null,
        };

        if ($byCategory !== null) {
            return $byCategory;
        }

        // 2. Key-shape overrides for the remaining screen categories.
        if ($last === 'title' || str_ends_with($key, '.title')) {
            return self::TITLE;
        }

        foreach (self::HELPER_HINTS as $hint) {
            if (str_contains($last, $hint)) {
                return self::HELPER;
            }
        }

        if (in_array($last, self::ACTION_WORDS, true)) {
            return self::ACTION;
        }

        // 3. Default: a plain field/content label.
        return self::LABEL;
    }

    /**
     * Vietnamese label + Filament badge color per kind (used by the Filament table).
     *
     * @return array{label: string, color: string}
     */
    public static function meta(?string $kind): array
    {
        return match ($kind) {
            self::NAV => ['label' => 'Menu / Điều hướng', 'color' => 'info'],
            self::TITLE => ['label' => 'Tiêu đề màn', 'color' => 'primary'],
            self::ACTION => ['label' => 'Nút / Hành động', 'color' => 'success'],
            self::STATUS => ['label' => 'Trạng thái', 'color' => 'warning'],
            self::NOTIFICATION => ['label' => 'Thông báo', 'color' => 'info'],
            self::ERROR => ['label' => 'Lỗi / Hệ thống', 'color' => 'danger'],
            self::HELPER => ['label' => 'Ghi chú / Mô tả', 'color' => 'gray'],
            self::LABEL => ['label' => 'Nhãn / Nội dung', 'color' => 'gray'],
            default => ['label' => (string) $kind, 'color' => 'gray'],
        };
    }

    /**
     * All kinds as value => label, for a Filament SelectFilter.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach ([
            self::NAV, self::TITLE, self::ACTION, self::LABEL,
            self::STATUS, self::NOTIFICATION, self::ERROR, self::HELPER,
        ] as $kind) {
            $options[$kind] = self::meta($kind)['label'];
        }

        return $options;
    }

    private static function lastSegment(string $key): string
    {
        $parts = explode('.', $key);

        return strtolower((string) end($parts));
    }
}
