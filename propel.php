<?php

// Used by vendor/bin/perpl CLI (model:build, database:reverse, etc.)
// Reads DB_* from the environment, falling back to .env.
// Do NOT hardcode credentials here — this file is committed.

// Real environment wins over .env, so CI and one-off DB_NAME=... overrides keep working.
if (class_exists(\Dotenv\Dotenv::class)) {
    \Dotenv\Dotenv::createUnsafeImmutable(__DIR__)->safeLoad();
}

$host   = getenv('DB_HOST') ?: '127.0.0.1';
$port   = getenv('DB_PORT') ?: '3306';
$name   = getenv('DB_NAME') ?: 'mech_library';
$user   = getenv('DB_USER') ?: 'root';
$pass   = getenv('DB_PASS') ?: '';

return [
    'propel' => [
        'database' => [
            'connections' => [
                'mech_library' => [
                    'adapter'    => 'mysql',
                    'classname'  => 'Propel\Runtime\Connection\ConnectionWrapper',
                    'dsn'        => "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
                    'user'       => $user,
                    'password'   => $pass,
                    'attributes' => [],
                ],
            ],
        ],
        'runtime' => [
            'defaultConnection' => 'mech_library',
            'connections' => ['mech_library'],
        ],
        'generator' => [
            'defaultConnection' => 'mech_library',
            'connections' => ['mech_library'],
        ],
        'paths' => [
            'projectDir'  => __DIR__,
            'schemaDir'   => __DIR__ . '/src/Model',
            'outputDir'   => __DIR__ . '/src',
            'migrationDir'=> __DIR__ . '/data/migrations',
        ],
    ],
];
