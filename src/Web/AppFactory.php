<?php

declare(strict_types=1);

namespace App\Web;

use App\Bootstrap;
use Slim\App;
use Slim\Factory\AppFactory as SlimAppFactory;

final class AppFactory
{
    /** @return App<\Psr\Container\ContainerInterface|null> */
    public static function create(string $rootDir): App
    {
        if (!isset($_ENV['DB_USER']) && !getenv('DB_USER')) {
            $envFile = $rootDir . '/.env';
            if (file_exists($envFile)) {
                \Dotenv\Dotenv::createUnsafeImmutable($rootDir)->safeLoad();
            }
        }

        Bootstrap::initPropel();

        $container = ContainerFactory::build($rootDir);
        SlimAppFactory::setContainer($container);
        $app = SlimAppFactory::create();

        $debug = filter_var(
            $_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG') ?: false,
            FILTER_VALIDATE_BOOLEAN,
        );
        $app->addErrorMiddleware($debug, true, true);

        (require __DIR__ . '/routes.php')($app);

        return $app;
    }
}
