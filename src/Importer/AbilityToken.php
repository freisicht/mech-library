<?php

declare(strict_types=1);

namespace App\Importer;

final class AbilityToken
{
    public function __construct(
        public readonly string $raw,
        public readonly string $tag,
        public readonly ?int $short = null,
        public readonly ?int $medium = null,
        public readonly ?int $long = null,
        public readonly ?string $value = null,
    ) {
    }
}
