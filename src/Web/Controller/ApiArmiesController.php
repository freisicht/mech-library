<?php

declare(strict_types=1);

namespace App\Web\Controller;

use App\Web\Repository\ArmyRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ApiArmiesController
{
    public function __construct(private readonly ArmyRepository $armies)
    {
    }

    public function list(Request $request, Response $response): Response
    {
        return $this->json($response, $this->armies->findAll());
    }

    public function create(Request $request, Response $response): Response
    {
        $body = $this->body($request);
        $name = trim((string) ($body['name'] ?? ''));
        if ($name === '') {
            return $this->json($response, ['error' => 'name is required'], 422);
        }

        $id = $this->armies->create($name);

        return $this->json($response, ['id' => $id, 'name' => $name], 201);
    }

    /** @param array<string, mixed> $args */
    public function rename(Request $request, Response $response, array $args): Response
    {
        $id   = (int) $args['id'];
        $body = $this->body($request);
        $name = trim((string) ($body['name'] ?? ''));
        if ($name === '') {
            return $this->json($response, ['error' => 'name is required'], 422);
        }

        if ($this->armies->findById($id) === null) {
            return $this->json($response, ['error' => 'not found'], 404);
        }

        $this->armies->rename($id, $name);

        return $this->json($response, ['id' => $id, 'name' => $name]);
    }

    /** @param array<string, mixed> $args */
    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        if ($this->armies->findById($id) === null) {
            return $this->json($response, ['error' => 'not found'], 404);
        }

        $this->armies->delete($id);

        return $response->withStatus(204);
    }

    private function json(Response $response, mixed $data, int $status = 200): Response
    {
        $body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $response->getBody()->write($body);

        return $response
            ->withHeader('Content-Type', 'application/json; charset=UTF-8')
            ->withStatus($status);
    }

    /** @return array<string, mixed> */
    private function body(Request $request): array
    {
        $parsed = $request->getParsedBody();
        if (is_array($parsed) && $parsed !== []) {
            return $parsed;
        }
        $raw = (string) $request->getBody();
        if ($raw !== '') {
            return (array) json_decode($raw, true);
        }
        return [];
    }
}
