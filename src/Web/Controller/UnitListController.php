<?php

declare(strict_types=1);

namespace App\Web\Controller;

use App\Web\Repository\FilterInput;
use App\Web\Repository\UnitRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment;

final class UnitListController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly UnitRepository $repo,
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $params  = $request->getQueryParams();
        if (isset($params['era']) && !is_array($params['era'])) {
            $params['era'] = [$params['era']];
        }
        $filter  = FilterInput::fromQueryParams($params);
        $options = $this->repo->getFilterOptions();

        $html = $this->twig->render('units/list.html.twig', [
            'filter'  => $filter,
            'options' => $options,
            'params'  => $params,
        ]);

        $response->getBody()->write($html);

        return $response->withHeader('Content-Type', 'text/html; charset=UTF-8');
    }
}
