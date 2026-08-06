<?php

declare(strict_types=1);

namespace App\Services\Localization;

final class TranslationPackChecksum
{
    /**
     * @param array<string, string> $values
     */
    public function canonicalJson(array $values): string
    {
        ksort($values, SORT_STRING);

        return json_encode(
            $values,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );
    }

    /**
     * @param array<string, string> $values
     */
    public function hash(array $values): string
    {
        return hash('sha256', $this->canonicalJson($values));
    }
}
