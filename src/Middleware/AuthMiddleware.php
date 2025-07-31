<?php
namespace App\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Response;

class AuthMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = strtoupper($request->getMethod());
        if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
            $apiKey = $request->getHeaderLine('X-API-Key');
            $valid = getenv('API_KEY') ?: 'secret';

            // ЛОГИРУЕМ для отладки
            error_log('HEADER X-API-Key: ' . $apiKey);
            error_log('ENV API_KEY: ' . getenv('API_KEY'));
            error_log('VALID from getenv or fallback: ' . $valid);

            if ($apiKey !== $valid) {
                $response = new Response();
                $response->getBody()->write('Unauthorized');
                return $response->withStatus(401);
            }
        }

        return $handler->handle($request);
    }
}

