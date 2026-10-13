<?php

declare(strict_types=1);

namespace App\Importer;

/**
 * Extracts the Alpha Strike block from a MUL unit page (/units/{slug}).
 *
 * The block is a grid of key/value cells (PV, Size, Move, TMM, Armor, Struct)
 * followed by a row of chips: "DMG s/m/l/e", "OV n", "TH n", one chip per
 * special ability ("CAR 4", "TUR (2/2/-,SRM2/2)") and finally the role.
 */
final class UnitPageParser
{
    /**
     * @return array{
     *     pv: int|null, size: int|null, move: string, tmm: int|null,
     *     armor: int|null, structure: int|null, threshold: int|null, overheat: int|null,
     *     damage: list<array{int, bool}>, chips: list<string>, image_url: string|null
     * }|null null when the page has no Alpha Strike stats
     */
    public function parse(string $html): ?array
    {
        $start = strpos($html, 'Alpha Strike Stats');
        if ($start === false) {
            return null;
        }
        $row   = strpos($html, 'u-abilrow', $start);
        $end   = $row === false ? false : strpos($html, '</div>', $row);
        $block = $end === false ? substr($html, $start, 4000) : substr($html, $start, $end - $start);

        $cells = [];
        if (preg_match_all('#<div class="k">([^<]*)</div>\s*<div class="v">(.*?)</div>#s', $block, $m, PREG_SET_ORDER)) {
            foreach ($m as [, $key, $value]) {
                $cells[strtolower(trim($key))] = $this->text($value);
            }
        }

        $result = [
            'pv'        => $this->int($cells['pv'] ?? null),
            'size'      => $this->int($cells['size'] ?? null),
            'move'      => $cells['move'] ?? '',
            'tmm'       => $this->int($cells['tmm'] ?? null),
            'armor'     => $this->int($cells['armor'] ?? null),
            'structure' => $this->int($cells['struct'] ?? null),
            'threshold' => null,
            'overheat'  => null,
            'damage'    => [],
            'chips'     => [],
            'image_url' => $this->imageUrl($html),
        ];

        foreach ($this->chips($block) as $chip) {
            if (preg_match('#^DMG\s+(\S+)$#', $chip, $d)) {
                foreach (explode('/', $d[1]) as $v) {
                    $result['damage'][] = [(int) $v, str_ends_with($v, '*')];
                }
            } elseif (preg_match('#^OV\s+(\d+)$#', $chip, $d)) {
                $result['overheat'] = (int) $d[1];
            } elseif (preg_match('#^TH\s+(\d+)$#', $chip, $d)) {
                $result['threshold'] = (int) $d[1];
            } else {
                $result['chips'][] = $chip;
            }
        }

        return $result;
    }

    /** @return list<string> */
    private function chips(string $block): array
    {
        $row = strstr($block, 'u-abilrow');
        if ($row === false) {
            return [];
        }
        // The long ability name sits in a nested span; only the code is wanted.
        $row = (string) preg_replace('#<span class="an">.*?</span>#s', '', $row);
        preg_match_all('#<span class="aschip">(.*?)</span>#s', $row, $m);

        return array_values(array_filter(array_map(fn (string $c): string => $this->text($c), $m[1])));
    }

    private function imageUrl(string $html): ?string
    {
        if (preg_match('#<img src="([^"]+)" alt="[^"]*\billustration"#', $html, $m)) {
            return html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5);
        }
        return null;
    }

    private function text(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5);
        return trim((string) preg_replace('#\s+#u', ' ', $text));
    }

    private function int(?string $value): ?int
    {
        return $value !== null && preg_match('#^-?\d+$#', $value) ? (int) $value : null;
    }
}
