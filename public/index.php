<?php
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Illuminate\Database\Capsule\Manager as Capsule;

date_default_timezone_set('Europe/Warsaw');

$app = AppFactory::create();

// Настройка Eloquent под MSSQL
$capsule = new Capsule;
$capsule->addConnection([
    'driver'   => 'sqlsrv',
    'host'     => '192.168.1.88',
    'database' => 'ERPXL_TSL',
    'username' => 'sa_tsl',
    'password' => '@nalizyGrudzien24@',
    'trust_server_certificate' => true,
    'charset'  => 'utf8',
    'prefix'   => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

// Маршруты
(require __DIR__ . '/../src/routes.php')($app);

$app->run();
