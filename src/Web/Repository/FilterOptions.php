<?php

declare(strict_types=1);

namespace App\Web\Repository;

final class FilterOptions
{
    /**
     * @param list<array{id: int, name: string}>                         $types
     * @param list<array{id: int, name: string}>                         $roles
     * @param list<array{id: int, name: string}>                         $technologies
     * @param list<array{id: int, name: string, start_year: int|null}>   $eras
     * @param list<string>                                               $abilityTags
     */
    public function __construct(
        public readonly array $types,
        public readonly array $roles,
        public readonly array $technologies,
        public readonly array $eras,
        public readonly array $abilityTags,
    ) {
    }
}
