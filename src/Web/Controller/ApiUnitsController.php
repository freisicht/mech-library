<?php

declare(strict_types=1);

namespace App\Web\Controller;

use App\Web\Repository\FilterInput;
use App\Web\Repository\UnitRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ApiUnitsController
{
    public function __construct(
        private readonly UnitRepository $repo,
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $filter = FilterInput::fromQueryParams($request->getQueryParams());
        $result = $this->repo->findFiltered($filter);

        $json = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $response->getBody()->write($json);

        return $response->withHeader('Content-Type', 'application/json; charset=UTF-8');
    }
}
