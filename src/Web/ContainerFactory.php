<?php

declare(strict_types=1);

namespace App\Web;

use App\Auth\CurrentUser;
use App\Auth\MattermostClient;
use App\Auth\MattermostProvider;
use DI\Container;
use DI\ContainerBuilder;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface as GuzzleClientInterface;
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
            Environment::class => static function (Container $c) use ($rootDir, $debug): Environment {
                $loader = new FilesystemLoader($rootDir . '/templates');

                $env = new Environment($loader, [
                    'cache'            => $debug ? false : $rootDir . '/var/cache/twig',
                    'auto_reload'      => true,
                    'strict_variables' => true,
                ]);

                $current = $c->get(CurrentUser::class);
                $env->addFunction(new \Twig\TwigFunction(
                    'current_user',
                    static fn () => $current->user(),
                ));

                return $env;
            },
            LoggerInterface::class => static function () use ($rootDir): LoggerInterface {
                $logger = new Logger('web');
                $logger->pushHandler(new StreamHandler(
                    $rootDir . '/var/log/web.log',
                    Level::Warning,
                ));

                return $logger;
            },
            CurrentUser::class => static fn (): CurrentUser => new CurrentUser(),
            GuzzleClientInterface::class => static fn (): GuzzleClientInterface => new GuzzleClient([
                'timeout' => 10.0,
            ]),
            MattermostProvider::class => static function (): MattermostProvider {
                $base     = self::requiredEnv('MM_BASE_URL');
                $clientId = self::env('MM_OAUTH_CLIENT_ID', '');
                $secret   = self::env('MM_OAUTH_CLIENT_SECRET', '');
                $redirect = self::requiredEnv('MM_OAUTH_REDIRECT_URI');
                return new MattermostProvider([
                    'baseUrl'      => $base,
                    'clientId'     => $clientId,
                    'clientSecret' => $secret,
                    'redirectUri'  => $redirect,
                ]);
            },
            MattermostClient::class => static function (Container $c): MattermostClient {
                return new MattermostClient(
                    $c->get(GuzzleClientInterface::class),
                    self::requiredEnv('MM_BASE_URL'),
                );
            },
        ]);

        return $builder->build();
    }

    private static function requiredEnv(string $key): string
    {
        $v = self::env($key, '');
        if ($v === '') {
            throw new \RuntimeException("{$key} is not set; see .env.example");
        }
        return $v;
    }

    private static function env(string $key, string $default): string
    {
        /** @var mixed $v */
        $v = $_ENV[$key] ?? getenv($key);
        if ($v === false || $v === '' || $v === null) {
            return $default;
        }
        return (string) $v;
    }
}
