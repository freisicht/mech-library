<?php

declare(strict_types=1);

namespace App\Tests\Web;

use App\Importer\UnitImporter;
use App\Model\UnitAbilitiesQuery;
use App\Model\UnitsQuery;
use App\Web\AppFactory;
use PHPUnit\Framework\TestCase;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;

abstract class WebTestCase extends TestCase
{
    /** @var App<\Psr\Container\ContainerInterface|null> */
    protected App $app;

    protected UnitImporter $importer;

    /** @var int[] */
    protected array $createdMulIds = [];

    protected function setUp(): void
    {
        $this->loadEnv();
        $_ENV['AUTH_DISABLED'] = '1';
        putenv('AUTH_DISABLED=1');
        $this->app     = AppFactory::create(dirname(__DIR__, 2));
        $this->importer = new UnitImporter();
    }

    protected function tearDown(): void
    {
        foreach ($this->createdMulIds as $mulId) {
            $unit = UnitsQuery::create()->findOneByMulId($mulId);
            if ($unit !== null) {
                UnitAbilitiesQuery::create()->filterByUnitId($unit->getId())->delete();
                $unit->delete();
            }
        }
    }

    protected function get(string $uri): \Psr\Http\Message\ResponseInterface
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', $uri);

        return $this->app->handle($request);
    }

    /** @param array<string, mixed> $payload */
    protected function post(string $uri, array $payload = []): \Psr\Http\Message\ResponseInterface
    {
        return $this->jsonRequest('POST', $uri, $payload);
    }

    /** @param array<string, mixed> $payload */
    protected function put(string $uri, array $payload = []): \Psr\Http\Message\ResponseInterface
    {
        return $this->jsonRequest('PUT', $uri, $payload);
    }

    protected function delete(string $uri): \Psr\Http\Message\ResponseInterface
    {
        $request = (new ServerRequestFactory())->createServerRequest('DELETE', $uri);
        return $this->app->handle($request);
    }

    /** @param array<string, mixed> $payload */
    private function jsonRequest(string $method, string $uri, array $payload): \Psr\Http\Message\ResponseInterface
    {
        $factory = new ServerRequestFactory();
        $request = $factory->createServerRequest($method, $uri)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json');

        if ($payload !== []) {
            $body = (new \Slim\Psr7\Factory\StreamFactory())->createStream(
                json_encode($payload, JSON_THROW_ON_ERROR),
            );
            $request = $request->withBody($body);
        }

        return $this->app->handle($request);
    }

    /** @return array<string, mixed> */
    protected function getJson(string $uri): array
    {
        $response = $this->get($uri);
        $body     = (string) $response->getBody();

        /** @var array<string, mixed> $data */
        $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        return $data;
    }

    /**
     * @param  array<string, mixed> $payload
     * @return array<string, mixed>
     */
    protected function postJson(string $uri, array $payload = []): array
    {
        $response = $this->post($uri, $payload);
        $body     = (string) $response->getBody();

        /** @var array<string, mixed> $data */
        $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        return $data;
    }

    /** @return array<string, mixed> */
    protected function makeUnit(int $mulId = 99901): array
    {
        $this->createdMulIds[] = $mulId;

        return [
            'Id'               => $mulId,
            'Name'             => 'Test Atlas',
            'Class'            => 'Atlas',
            'Variant'          => 'TS-T1',
            'Tonnage'          => 100,
            'BattleValue'      => 1000,
            'Cost'             => 5000000,
            'Rules'            => 'Standard',
            'DateIntroduced'   => '3025',
            'EraId'            => 9999,
            'EraStart'         => 2571,
            'ImageUrl'         => null,
            'IsPublished'      => true,
            'Release'          => 1.0,
            'Type'             => ['Id' => 18, 'Name' => 'BattleMech'],
            'Technology'       => ['Id' => 1,  'Name' => 'Inner Sphere'],
            'Role'             => ['Id' => 1,  'Name' => 'Juggernaut'],
            'BFType'           => 'BM',
            'BFSize'           => 4,
            'BFMove'           => '6"',
            'BFTMM'            => 0,
            'BFArmor'          => 10,
            'BFStructure'      => 8,
            'BFThreshold'      => 0,
            'BFDamageShort'    => 5,
            'BFDamageShortMin' => false,
            'BFDamageMedium'   => 5,
            'BFDamageMediumMin' => false,
            'BFDamageLong'     => 1,
            'BFDamageLongMin'  => false,
            'BFDamageExtreme'  => 0,
            'BFDamageExtemeMin' => false,
            'BFOverheat'       => 0,
            'BFPointValue'     => 52,
            'BFAbilities'      => 'AC2/2/-,IF1',
        ];
    }

    private function loadEnv(): void
    {
        if (!isset($_ENV['DB_USER']) && !getenv('DB_USER')) {
            $envFile = dirname(__DIR__, 2) . '/.env';
            if (file_exists($envFile)) {
                \Dotenv\Dotenv::createUnsafeImmutable(dirname(__DIR__, 2))->safeLoad();
            }
        }
    }
}
