<?php

declare(strict_types=1);

namespace App\Web\Controller;

use App\Web\Repository\FormationRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment;

final class FormationPrintController
{
    public function __construct(
        private readonly FormationRepository $formations,
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

        $html = $this->twig->render('formations/print.html.twig', ['formation' => $formation]);
        $response->getBody()->write($html);

        return $response->withHeader('Content-Type', 'text/html; charset=UTF-8');
    }
}
