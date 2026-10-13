<?php

declare(strict_types=1);

namespace App\Web\Controller;

use App\Formation\FormationTypeRegistry;
use App\Web\Repository\FormationRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ApiFormationsController
{
    public function __construct(private readonly FormationRepository $formations)
    {
    }

    public function listAll(Request $request, Response $response): Response
    {
        return $this->json($response, $this->formations->findAllForSelector());
    }

    public function types(Request $request, Response $response): Response
    {
        $types = [];
        foreach (FormationTypeRegistry::groups() as $group => $slugs) {
            foreach ($slugs as $slug) {
                $t = FormationTypeRegistry::get($slug) ?? [];
                $types[] = ['slug' => $slug, 'name' => $t['name'], 'faction' => $t['faction'], 'group' => $group];
            }
        }
        return $this->json($response, $types);
    }

    public function getActive(Request $request, Response $response): Response
    {
        $formation = $this->formations->findActive();
        return $this->json($response, ['formation' => $formation]);
    }

    /** @param array<string, mixed> $args */
    public function show(Request $request, Response $response, array $args): Response
    {
        $formation = $this->formations->findById((int) $args['id']);
        if ($formation === null) {
            return $this->json($response, ['error' => 'not found'], 404);
        }
        return $this->json($response, $formation);
    }

    public function create(Request $request, Response $response): Response
    {
        $body = $this->body($request);
        $name = trim((string) ($body['name'] ?? ''));
        if ($name === '') {
            return $this->json($response, ['error' => 'name is required'], 422);
        }

        $type     = (string) ($body['formation_type'] ?? 'custom');
        $armyId   = isset($body['army_id']) ? (int) $body['army_id'] : null;
        $pvBudget = isset($body['pv_budget']) ? (int) $body['pv_budget'] : null;

        if (FormationTypeRegistry::get($type) === null) {
            return $this->json($response, ['error' => "Unknown formation type: {$type}"], 422);
        }

        $id = $this->formations->create($name, $type, $armyId, $pvBudget);

        return $this->json($response, $this->formations->findById($id), 201);
    }

    /** @param array<string, mixed> $args */
    public function update(Request $request, Response $response, array $args): Response
    {
        $id         = (int) $args['id'];
        $formation  = $this->formations->findById($id);
        if ($formation === null) {
            return $this->json($response, ['error' => 'not found'], 404);
        }

        $body = $this->body($request);
        $name = trim((string) ($body['name'] ?? $formation['name']));
        $type = (string) ($body['formation_type'] ?? $formation['formation_type']);

        if (isset($body['formation_type']) && FormationTypeRegistry::get($type) === null) {
            return $this->json($response, ['error' => "Unknown formation type: {$type}"], 422);
        }

        $pvBudget = array_key_exists('pv_budget', $body)
            ? ($body['pv_budget'] !== null ? (int) $body['pv_budget'] : null)
            : $formation['pv_budget'];

        $this->formations->update($id, $name, $type, $pvBudget);

        return $this->json($response, $this->formations->findById($id));
    }

    /** @param array<string, mixed> $args */
    public function activate(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        if ($this->formations->findById($id) === null) {
            return $this->json($response, ['error' => 'not found'], 404);
        }

        $this->formations->setActive($id);

        return $this->json($response, $this->formations->findById($id));
    }

    public function deactivate(Request $request, Response $response): Response
    {
        $this->formations->deactivateAll();
        return $response->withStatus(204);
    }

    /** @param array<string, mixed> $args */
    public function delete(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        if ($this->formations->findById($id) === null) {
            return $this->json($response, ['error' => 'not found'], 404);
        }

        $this->formations->delete($id);

        return $response->withStatus(204);
    }

    /** @param array<string, mixed> $args */
    public function addUnit(Request $request, Response $response, array $args): Response
    {
        $formationId = (int) $args['id'];
        if ($this->formations->findById($formationId) === null) {
            return $this->json($response, ['error' => 'formation not found'], 404);
        }

        $body   = $this->body($request);
        $unitId = isset($body['unit_id']) ? (int) $body['unit_id'] : 0;
        if ($unitId === 0) {
            return $this->json($response, ['error' => 'unit_id is required'], 422);
        }

        $added = $this->formations->addUnit($formationId, $unitId);
        $status = $added ? 201 : 200;

        return $this->json($response, $this->formations->findById($formationId), $status);
    }

    /** @param array<string, mixed> $args */
    public function removeUnit(Request $request, Response $response, array $args): Response
    {
        $formationId = (int) $args['id'];
        $unitId      = (int) $args['unitId'];

        $this->formations->removeUnit($formationId, $unitId);

        $formation = $this->formations->findById($formationId);
        if ($formation === null) {
            return $this->json($response, ['error' => 'formation not found'], 404);
        }

        return $this->json($response, $formation);
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
