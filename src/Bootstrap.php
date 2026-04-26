<?php

declare(strict_types=1);

namespace App;

use Propel\Runtime\Adapter\Pdo\MysqlAdapter;
use Propel\Runtime\Connection\ConnectionManagerSingle;
use Propel\Runtime\Propel;
use Propel\Runtime\ServiceContainer\StandardServiceContainer;
use RuntimeException;

final class Bootstrap
{
    private static bool $initialized = false;

    public static function initPropel(): void
    {
        if (self::$initialized) {
            return;
        }

        $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1';
        $port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '3306';
        $name = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'mech_library';
        $user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: throw new RuntimeException('DB_USER not set');
        $pass = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: '';

        $manager = new ConnectionManagerSingle('mech_library');
        $manager->setConfiguration([
            'adapter'   => 'mysql',
            'classname' => 'Propel\Runtime\Connection\ConnectionWrapper',
            'dsn'       => "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
            'user'      => $user,
            'password'  => $pass,
        ]);

        /** @var StandardServiceContainer $serviceContainer */
        $serviceContainer = Propel::getServiceContainer();
        $serviceContainer->setAdapterClass('mech_library', MysqlAdapter::class);
        $serviceContainer->setConnectionManager($manager);
        $serviceContainer->setDefaultDatasource('mech_library');

        $loadDb = dirname(__DIR__) . '/generated-conf/loadDatabase.php';
        if (file_exists($loadDb)) {
            require_once $loadDb;
        }

        self::$initialized = true;
    }
}
