<?php

declare(strict_types=1);

namespace App\Pdf;

use Mpdf\Mpdf;
use Twig\Environment;

final class AlphaStrikePdfRenderer
{
    public function __construct(private readonly Environment $twig)
    {
    }

    /**
     * @param array<string, mixed> $formation
     */
    public function renderFormation(array $formation, int $skill = 4): string
    {
        $html = $this->twig->render('cards/sheet.html.twig', [
            'formation' => $formation,
            'skill'     => $skill,
        ]);

        $tmpDir = \dirname(__DIR__, 2) . '/var/cache/mpdf';
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0o775, true);
        }

        $mpdf = new Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4',
            'margin_left'   => 8,
            'margin_right'  => 8,
            'margin_top'    => 8,
            'margin_bottom' => 8,
            'tempDir'       => $tmpDir,
        ]);

        $mpdf->WriteHTML($html);

        /** @var string $pdf */
        $pdf = $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
        return $pdf;
    }
}
