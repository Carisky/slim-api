<?php

use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\LimitChecker;

return function (App $app) {

    $app->get('/api/limits/check/{pc}', function (Request $request, Response $response, array $args): Response {
        $pc = $args['pc'];
        $checker = new LimitChecker();
        $result = $checker->check($pc);

        $response->getBody()->write((string)$result['code']);
        return $response->withHeader('Content-Type', 'text/plain')->withStatus(200);
    });

    $app->get('/api/limits/users', function (Request $request, Response $response): Response {
        $checker = new LimitChecker();
        $data = $checker->getUsersTimeLeft();
        $response->getBody()->write(json_encode($data));
        return $response->withHeader("Content-Type", "application/json")->withStatus(200);
    });

    $app->post('/api/limits/users/session/stop/{user}', function (Request $request, Response $response, array $args): Response {
        $user = $args['user'];
        $checker = new LimitChecker();
        $result = $checker->stopSession($user);

        $status = $result ? 200 : 404;
        $body = $result ? "Session stopped for user: $user" : "No active session found for user: $user";

        $response->getBody()->write($body);
        return $response->withStatus($status)->withHeader('Content-Type', 'text/plain');
    });

};
