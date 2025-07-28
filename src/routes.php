<?php
use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\LimitChecker;

/**
 * @OA\Get(
 *   path="/api/limits/check/{pc}",
 *   summary="Check license limit for PC",
 *   @OA\Parameter(
 *     name="pc",
 *     in="path",
 *     required=true,
 *     description="PC name",
 *     @OA\Schema(type="string")
 *   ),
 *   @OA\Response(
 *     response=200,
 *     description="OK"
 *   ),
 *   @OA\Response(
 *     response=404,
 *     description="Session not found"
 *   ),
 *   @OA\Response(
 *     response=429,
 *     description="User over limit"
 *   )
 * )
 */
function checkLimit(Request $request, Response $response, array $args): Response {
    $pc = $args['pc'];
    $checker = new LimitChecker();
    $result = $checker->check($pc);

    if ($result['status'] === 404) {
        return $response->withStatus(404);
    }

    if ($result['status'] === 429) {
        return $response->withStatus(429);
    }

    $response->getBody()->write(json_encode([
        'user' => $result['user'] ?? null,
    ]));
    return $response->withHeader('Content-Type', 'application/json');
}

return function (App $app) {
    $app->get('/api/limits/check/{pc}', 'checkLimit');
};