<?php

declare(strict_types=1);

use App\Auth\AuthConfig;
use App\Web\Controller\ApiArmiesController;
use App\Web\Controller\ApiFormationsController;
use App\Web\Controller\ApiUnitsController;
use App\Web\Controller\AuthController;
use App\Web\Controller\FormationPrintController;
use App\Web\Controller\FormationPrintPdfController;
use App\Web\Controller\MeController;
use App\Web\Controller\OAuthController;
use App\Web\Controller\UnitDetailController;
use App\Web\Controller\UnitListController;
use Slim\App;

return static function (App $app): void {
    // Auth, see docs/AUTH.md
    $app->get('/auth/login', [AuthController::class, 'showLogin']);
    $app->get('/auth/logout', [AuthController::class, 'logout']);
    $container = $app->getContainer();
    if ($container !== null && $container->get(AuthConfig::class)->isOAuth()) {
        $app->get('/auth/start', [OAuthController::class, 'start']);
        $app->get('/auth/callback', [OAuthController::class, 'callback']);
    } else {
        $app->post('/auth/login', [AuthController::class, 'login']);
    }

    // Current user (for UI)
    $app->get('/api/me', [MeController::class, 'me']);
    $app->get('/api/me/groups', [MeController::class, 'groups']);

    // Phase 2 — unit list + detail
    $app->get('/', UnitListController::class);
    $app->get('/units/{id:[0-9]+}', UnitDetailController::class);
    $app->get('/api/units', ApiUnitsController::class);

    // Phase 3 — formation type catalogue
    $app->get('/api/formation-types', [ApiFormationsController::class, 'types']);

    // Phase 3 — all formations (flat list for force-panel selector)
    $app->get('/api/formations', [ApiFormationsController::class, 'listAll']);

    // Phase 3 — active formation (force panel data source)
    $app->get('/api/formations/active', [ApiFormationsController::class, 'getActive']);
    $app->delete('/api/formations/active', [ApiFormationsController::class, 'deactivate']);

    // Phase 3 — formation CRUD
    $app->get('/api/formations/{id:[0-9]+}', [ApiFormationsController::class, 'show']);
    $app->post('/api/formations', [ApiFormationsController::class, 'create']);
    $app->put('/api/formations/{id:[0-9]+}', [ApiFormationsController::class, 'update']);
    $app->post('/api/formations/{id:[0-9]+}/activate', [ApiFormationsController::class, 'activate']);
    $app->delete('/api/formations/{id:[0-9]+}', [ApiFormationsController::class, 'delete']);

    // Phase 3 — formation unit management
    $app->post('/api/formations/{id:[0-9]+}/units', [ApiFormationsController::class, 'addUnit']);
    $app->delete(
        '/api/formations/{id:[0-9]+}/entries/{entryId:[0-9]+}',
        [ApiFormationsController::class, 'removeEntry'],
    );

    // Phase 3 — army CRUD
    $app->get('/api/armies', [ApiArmiesController::class, 'list']);
    $app->post('/api/armies', [ApiArmiesController::class, 'create']);
    $app->put('/api/armies/{id:[0-9]+}', [ApiArmiesController::class, 'rename']);
    $app->delete('/api/armies/{id:[0-9]+}', [ApiArmiesController::class, 'delete']);

    // Phase 3 — print view (HTML summary table)
    $app->get('/formations/{id:[0-9]+}/print', FormationPrintController::class);

    // Phase 3 — Alpha Strike card sheet (PDF)
    $app->get('/formations/{id:[0-9]+}/print.pdf', FormationPrintPdfController::class);
};
