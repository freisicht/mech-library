<?php

declare(strict_types=1);

use App\Web\AppFactory;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$app = AppFactory::create(dirname(__DIR__));
$app->run();
