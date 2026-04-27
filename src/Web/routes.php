<?php

declare(strict_types=1);

use App\Web\Controller\ApiUnitsController;
use App\Web\Controller\UnitDetailController;
use App\Web\Controller\UnitListController;
use Slim\App;

return static function (App $app): void {
    $app->get('/', UnitListController::class);
    $app->get('/units/{id:[0-9]+}', UnitDetailController::class);
    $app->get('/api/units', ApiUnitsController::class);
};
