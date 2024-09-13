<?php
namespace Test;

use ByJG\RestServer\HttpRequestHandler;
use ByJG\RestServer\Route\OpenApiRouteList;
use Exception;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/classes/Handler.php';
require_once __DIR__ . '/classes/Pet.php';
require_once __DIR__ . '/classes/Category.php';
require_once __DIR__ . '/classes/Tag.php';

$specification = __DIR__ . '/' . getenv('SPEC') .  '.json';

if (!file_exists($specification)) {
    throw new Exception("file $specification does not exists. Are you set the environment SPEC=openapi ?");
}

$routeDefinition = new OpenApiRouteList($specification);

$restServer = new HttpRequestHandler();
$restServer->handle($routeDefinition);

