<?php
use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\User;

/**
 * @OA\Get(
 *   path="/users",
 *   summary="Get all users",
 *   @OA\Response(
 *     response=200,
 *     description="List of users",
 *     @OA\JsonContent(
 *       type="array",
 *       @OA\Items(ref="#/components/schemas/User")
 *     )
 *   )
 * )
 */
function getUsers(Request $request, Response $response): Response {
    $users = User::all();
    $response->getBody()->write($users->toJson());
    return $response->withHeader('Content-Type', 'application/json');
}

/**
 * @OA\Get(
 *   path="/ping",
 *   summary="Ping test",
 *   @OA\Response(response=200, description="pong")
 * )
 */
function ping(Request $request, Response $response): Response {
    $response->getBody()->write(json_encode(['pong' => true]));
    return $response->withHeader('Content-Type', 'application/json');
}

return function (App $app) {
    $app->get('/users', 'getUsers');
    $app->get('/ping', 'ping');
};
