<?php

declare(strict_types=1);

namespace App\Pdf;

use Mpdf\Mpdf;
use Twig\Environment;

final class AlphaStrikePdfRenderer
{
    private readonly string $rootDir;

    public function __construct(private readonly Environment $twig)
    {
        $this->rootDir = \dirname(__DIR__, 2);
    }

    /**
     * @param array<string, mixed> $formation
     */
    public function renderFormation(array $formation, int $skill = 4): string
    {
        $formation['units'] = $this->enrichUnits($formation['units'] ?? []);

        $html = $this->twig->render('cards/sheet.html.twig', [
            'formation' => $formation,
            'skill'     => $skill,
        ]);

        $tmpDir = $this->rootDir . '/var/cache/mpdf';
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0o775, true);
        }

        $defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();
        $defaultConfig     = (new \Mpdf\Config\ConfigVariables())->getDefaults();

        $mpdf = new Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4',
            'margin_left'   => 8,
            'margin_right'  => 8,
            'margin_top'    => 8,
            'margin_bottom' => 8,
            'tempDir'       => $tmpDir,
            'fontDir'       => array_merge(
                $defaultConfig['fontDir'],
                [$this->rootDir . '/public/assets/fonts'],
            ),
            'fontdata'      => $defaultFontConfig['fontdata'] + [
                'orbitron' => [
                    'R'  => 'Orbitron-Regular.ttf',
                    'B'  => 'Orbitron-Bold.ttf',
                ],
                'orbitronblack' => [
                    'R'  => 'Orbitron-Black.ttf',
                ],
                'jetbrainsmono' => [
                    'R'  => 'JetBrainsMono-Regular.ttf',
                    'B'  => 'JetBrainsMono-Bold.ttf',
                ],
            ],
        ]);

        $mpdf->WriteHTML($html);

        /** @var string $pdf */
        $pdf = $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
        return $pdf;
    }

    /**
     * Resolve image_path → absolute filesystem path that mpdf can read.
     *
     * @param  list<array<string, mixed>> $units
     * @return list<array<string, mixed>>
     */
    private function enrichUnits(array $units): array
    {
        return array_map(function (array $u): array {
            $u['image_url'] = null;
            $path = $u['image_path'] ?? null;
            if (is_string($path) && $path !== '') {
                $absolute = $this->rootDir . '/public/' . ltrim($path, '/');
                if (is_file($absolute)) {
                    $u['image_url'] = $absolute;
                }
            }
            return $u;
        }, $units);
    }
}
