<?php

declare(strict_types=1);

namespace App\Importer;

interface Lookups
{
    /** Local id for a lookup name in one of the lookup tables, created if missing. */
    public function resolve(string $table, string $name): int;
}
