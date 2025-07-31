<?php

use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\LimitChecker;
use App\Models\GroupModuleLimit;
use App\Models\UserGroup;

return function (App $app) {

    // ----- Прочие роуты -----
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

    // ----- Group module limits -----
    $app->get('/api/group-module-limits', function (Request $request, Response $response): Response {
        $limits = GroupModuleLimit::all();
        $response->getBody()->write($limits->toJson());
        return $response->withHeader('Content-Type', 'application/json');
    });

    $app->get('/api/group-module-limits/{id}', function (Request $request, Response $response, array $args): Response {
        $limit = GroupModuleLimit::find($args['id']);
        if (!$limit) {
            return $response->withStatus(404);
        }
        $response->getBody()->write($limit->toJson());
        return $response->withHeader('Content-Type', 'application/json');
    });

    $app->post('/api/group-module-limits', function (Request $request, Response $response): Response {
        $data = json_decode($request->getBody()->getContents(), true);
        $limit = new GroupModuleLimit();
        $limit->GroupCode = $data['GroupCode'] ?? null;
        $limit->Module = $data['Module'] ?? null;
        $limit->Hour = $data['Hour'] ?? null;
        $limit->MaxLicenses = $data['MaxLicenses'] ?? null;
        $limit->save();
        $response->getBody()->write($limit->toJson());
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    });

    $app->put('/api/group-module-limits/{id}', function (Request $request, Response $response, array $args): Response {
        $limit = GroupModuleLimit::find($args['id']);
        if (!$limit) {
            return $response->withStatus(404);
        }
        $data = json_decode($request->getBody()->getContents(), true);
        foreach (['GroupCode', 'Module', 'Hour', 'MaxLicenses'] as $field) {
            if (array_key_exists($field, $data)) {
                $limit->$field = $data[$field];
            }
        }
        $limit->save();
        $response->getBody()->write($limit->toJson());
        return $response->withHeader('Content-Type', 'application/json');
    });

    $app->delete('/api/group-module-limits/{id}', function (Request $request, Response $response, array $args): Response {
        $limit = GroupModuleLimit::find($args['id']);
        if (!$limit) {
            return $response->withStatus(404);
        }
        $limit->delete();
        return $response->withStatus(204);
    });

    // ----- User groups -----
    $app->get('/api/user-groups', function (Request $request, Response $response): Response {
        $groups = UserGroup::all();
        $response->getBody()->write($groups->toJson());
        return $response->withHeader('Content-Type', 'application/json');
    });

    $app->get('/api/user-groups/{userName}', function (Request $request, Response $response, array $args): Response {
        $group = UserGroup::find($args['userName']); // userName = PK
        if (!$group) {
            return $response->withStatus(404);
        }
        $response->getBody()->write($group->toJson());
        return $response->withHeader('Content-Type', 'application/json');
    });

    $app->post('/api/user-groups', function (Request $request, Response $response): Response {
        $data = json_decode($request->getBody()->getContents(), true);
        $group = new UserGroup();
        $group->UserName = $data['UserName'] ?? null;
        $group->Group = $data['Group'] ?? null;
        $group->WindowsUser = $data['WindowsUser'] ?? null;
        $group->save();
        $response->getBody()->write($group->toJson());
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    });

    $app->put('/api/user-groups/{userName}', function (Request $request, Response $response, array $args): Response {
        $group = UserGroup::find($args['userName']);
        if (!$group) {
            return $response->withStatus(404);
        }
        $data = json_decode($request->getBody()->getContents(), true);
        foreach (['Group', 'WindowsUser'] as $field) {
            if (array_key_exists($field, $data)) {
                $group->$field = $data[$field];
            }
        }
        $group->save();
        $response->getBody()->write($group->toJson());
        return $response->withHeader('Content-Type', 'application/json');
    });

    $app->delete('/api/user-groups/{userName}', function (Request $request, Response $response, array $args): Response {
        $group = UserGroup::find($args['userName']);
        if (!$group) {
            return $response->withStatus(404);
        }
        $group->delete();
        return $response->withStatus(204);
    });
};
