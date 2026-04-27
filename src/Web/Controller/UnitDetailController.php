<?php

declare(strict_types=1);

namespace App\Web\Controller;

use App\Web\Repository\UnitRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment;

final class UnitDetailController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly UnitRepository $repo,
    ) {
    }

    /** @param array<string, string> $args */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $unit = $this->repo->findById((int) $args['id']);

        if ($unit === null) {
            $html = $this->twig->render('error/404.html.twig', ['id' => (int) $args['id']]);
            $response->getBody()->write($html);

            return $response
                ->withStatus(404)
                ->withHeader('Content-Type', 'text/html; charset=UTF-8');
        }

        $html = $this->twig->render('units/detail.html.twig', ['unit' => $unit]);
        $response->getBody()->write($html);

        return $response->withHeader('Content-Type', 'text/html; charset=UTF-8');
    }
}
