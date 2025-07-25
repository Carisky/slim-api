<?php
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Illuminate\Database\Capsule\Manager as Capsule;

$app = AppFactory::create();

// Настройка Eloquent под Docker
$capsule = new Capsule;
$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => 'db', // << это имя docker-сервиса из docker-compose.yml
    'database'  => 'slim',
    'username'  => 'slim',
    'password'  => 'slim',
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

// Маршруты
(require __DIR__ . '/../src/routes.php')($app);

$app->run();
