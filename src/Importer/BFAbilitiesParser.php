<?php

declare(strict_types=1);

namespace App\Importer;

/**
 * Parses the BFAbilities string from the MUL API into typed tokens.
 *
 * Four token shapes per docs/MUL_API.md:
 *   CASE          -> flag only (no value)
 *   IF1           -> tag + single integer rating
 *   AC2/2/-       -> tag + per-range short/medium/long damage ('-' = none)
 *   HT3/2         -> tag + composite value stored as-is
 */
final class BFAbilitiesParser
{
    /**
     * @return AbilityToken[]
     */
    public function parse(?string $raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }

        $tokens = [];
        foreach (explode(',', $raw) as $token) {
            $token = trim($token);
            if ($token === '') {
                continue;
            }
            $tokens[] = $this->parseToken($token);
        }

        return $tokens;
    }

    private function parseToken(string $raw): AbilityToken
    {
        // TAG<N>/<M>/<L>  -- three slash-separated values after tag (may be '-')
        if (preg_match('/^([A-Z][A-Z0-9]*)(\d+)\/(\d+|-)\/(\d+|-)$/', $raw, $m)) {
            return new AbilityToken(
                raw: $raw,
                tag: $m[1],
                short: (int) $m[2],
                medium: $m[3] === '-' ? null : (int) $m[3],
                long: $m[4] === '-' ? null : (int) $m[4],
            );
        }

        // TAG<N>/<M>  -- two slash-separated values (composite, store as value)
        if (preg_match('/^([A-Z][A-Z0-9]*)(\d+\/\d+)$/', $raw, $m)) {
            return new AbilityToken(raw: $raw, tag: $m[1], value: $m[2]);
        }

        // TAG<N>  -- single integer rating
        if (preg_match('/^([A-Z][A-Z0-9]*)(\d+)$/', $raw, $m)) {
            return new AbilityToken(raw: $raw, tag: $m[1], value: $m[2]);
        }

        // FLAG only (letters only, possibly with trailing digits that aren't separated)
        // Fallback: store raw as tag, no value
        if (preg_match('/^([A-Z][A-Z0-9]*)$/', $raw, $m)) {
            return new AbilityToken(raw: $raw, tag: $m[1]);
        }

        // Anything unrecognised: store entirely in tag + raw
        return new AbilityToken(raw: $raw, tag: $raw);
    }
}
