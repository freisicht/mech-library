<?php

declare(strict_types=1);

namespace App\Doctor;

final class CheckResult
{
    public function __construct(
        public readonly string $section,
        public readonly Status $status,
        public readonly string $message,
        public readonly ?string $hint = null,
    ) {
    }
}
