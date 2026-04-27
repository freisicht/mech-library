<?php

declare(strict_types=1);

namespace App\Formation;

final class ComplianceResult
{
    /** @param list<string> $unmetRequirements */
    public function __construct(
        public readonly bool $compliant,
        public readonly array $unmetRequirements,
    ) {
    }

    public static function ok(): self
    {
        return new self(true, []);
    }

    /** @param list<string> $unmet */
    public static function fail(array $unmet): self
    {
        return new self(false, $unmet);
    }

    /** @return array{compliant: bool, unmet_requirements: list<string>} */
    public function toArray(): array
    {
        return [
            'compliant'          => $this->compliant,
            'unmet_requirements' => $this->unmetRequirements,
        ];
    }
}
