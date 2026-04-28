<?php

declare(strict_types=1);

namespace App\Web\Controller;

use App\Pdf\AlphaStrikePdfRenderer;
use App\Web\Repository\FormationRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment;

final class FormationPrintPdfController
{
    public function __construct(
        private readonly FormationRepository $formations,
        private readonly AlphaStrikePdfRenderer $renderer,
        private readonly Environment $twig,
    ) {
    }

    /** @param array<string, mixed> $args */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $formation = $this->formations->findById((int) $args['id']);
        if ($formation === null) {
            $html = $this->twig->render('error/404.html.twig');
            $response->getBody()->write($html);
            return $response->withStatus(404);
        }

        $skillParam = $request->getQueryParams()['skill'] ?? '4';
        $skill = max(0, min(7, (int) $skillParam));

        $pdf = $this->renderer->renderFormation($formation, $skill);

        $filename = $this->slug((string) $formation['name']) . '-cards.pdf';

        $response->getBody()->write($pdf);
        return $response
            ->withHeader('Content-Type', 'application/pdf')
            ->withHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->withHeader('Cache-Control', 'private, max-age=0, must-revalidate');
    }

    private function slug(string $name): string
    {
        $slug = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $name) ?? 'formation');
        return trim($slug, '-') ?: 'formation';
    }
}
