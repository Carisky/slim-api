<?php
use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\LimitChecker;

function checkLimit(Request $request, Response $response, array $args): Response {
    $pc = $args['pc'];
    $checker = new LimitChecker();
    $result = $checker->check($pc);

    $response->getBody()->write((string)$result['code']);

    return $response
        ->withHeader('Content-Type', 'text/plain')
        ->withStatus(200);
}
function usersInfo(Request $request, Response $response): Response {
    $checker = new LimitChecker();
    $data = $checker->getUsersTimeLeft();
    $response->getBody()->write(json_encode($data));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus(200);
}




return function (App $app) {
    $app->get('/api/limits/check/{pc}', 'checkLimit');
    $app->get('/api/limits/users', 'usersInfo');
};