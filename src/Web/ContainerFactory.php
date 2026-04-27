<?php

declare(strict_types=1);

namespace App\Web;

use DI\Container;
use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class ContainerFactory
{
    public static function build(string $rootDir): Container
    {
        $debug = filter_var(
            $_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG') ?: true,
            FILTER_VALIDATE_BOOLEAN,
        );

        $builder = new ContainerBuilder();
        $builder->addDefinitions([
            Environment::class => static function () use ($rootDir, $debug): Environment {
                $loader = new FilesystemLoader($rootDir . '/templates');

                return new Environment($loader, [
                    'cache'            => $debug ? false : $rootDir . '/var/cache/twig',
                    'auto_reload'      => true,
                    'strict_variables' => true,
                ]);
            },
            LoggerInterface::class => static function () use ($rootDir): LoggerInterface {
                $logger = new Logger('web');
                $logger->pushHandler(new StreamHandler(
                    $rootDir . '/var/log/web.log',
                    Level::Warning,
                ));

                return $logger;
            },
        ]);

        return $builder->build();
    }
}
