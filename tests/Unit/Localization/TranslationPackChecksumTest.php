<?php

declare(strict_types=1);

namespace Tests\Unit\Localization;

use App\Services\Localization\TranslationPackChecksum;
use PHPUnit\Framework\TestCase;

final class TranslationPackChecksumTest extends TestCase
{
    public function test_checksum_matches_cross_platform_vector(): void
    {
        $values = [
            'common.cancel' => 'Cancel',
            'common.save' => 'Save',
            'welcome' => 'Xin chào',
        ];

        $service = new TranslationPackChecksum();

        self::assertSame(
            '{"common.cancel":"Cancel","common.save":"Save","welcome":"Xin chào"}',
            $service->canonicalJson($values),
        );
        self::assertSame(
            'a118d80aabe22c730703b9036ef88121f2b723add4a7fd480480d4aecf02ace9',
            $service->hash($values),
        );
    }
}
