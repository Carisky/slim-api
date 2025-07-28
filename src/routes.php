<?php
use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\LimitChecker;

function checkLimit(Request $request, Response $response, array $args): Response {
    $pc = $args['pc'];
    $checker = new LimitChecker();
    $result = $checker->check($pc);

    $response->getBody()->write(json_encode([
        'code' => $result['code'],
    ]));

    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(200);
}


return function (App $app) {
    $app->get('/api/limits/check/{pc}', 'checkLimit');
};