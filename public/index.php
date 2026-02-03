<?php
declare(strict_types=1);

use App\App;
use App\Http\Request;

require __DIR__ . '/../vendor/autoload.php';

$app = new App();
$request = Request::fromGlobals();
//print_r($request);
$response = $app->handle($request);
//print_r($response);
$response->send();
