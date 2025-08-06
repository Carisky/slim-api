<?php

use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\LimitChecker;
use App\Models\GroupModuleLimit;
use App\Models\UserGroup;
use App\Models\ExceptionUser;
use OpenApi\Annotations as OA;
use OpenApi\Generator;

return function (App $app) {

    // ----- Прочие роуты -----

    /**
     * @OA\Get(
     *     path="/api/limits/check/{pc}",
     *     summary="Check license limits for a PC",
     *     tags={"Limits"},
     *     @OA\Parameter(name="pc", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="Limit code",
     *         @OA\MediaType(mediaType="text/plain", @OA\Schema(type="string"))
     *     )
     * )
     */
    $app->get('/api/limits/check/{pc}', function (Request $request, Response $response, array $args): Response {
        $pc = $args['pc'];
        $checker = new LimitChecker();
        $result = $checker->check($pc);
        $response->getBody()->write((string)$result['code']);
        return $response->withHeader('Content-Type', 'text/plain')->withStatus(200);
    });

    /**
     * @OA\Get(
     *     path="/api/limits/users",
     *     summary="Get remaining time for active users",
     *     tags={"Limits"},
     *     @OA\Response(
     *         response=200,
     *         description="Time left for users",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/UserTimeLeft"))
     *     )
     * )
     */
    $app->get('/api/limits/users', function (Request $request, Response $response): Response {
        $checker = new LimitChecker();
        $data = $checker->getUsersTimeLeft();
        $response->getBody()->write(json_encode($data));
        return $response->withHeader("Content-Type", "application/json")->withStatus(200);
    });

    /**
     * @OA\Get(
     *     path="/api/schedule/{userName}",
     *     summary="Get allowed schedule for user",
     *     tags={"Limits"},
     *     @OA\Parameter(name="userName", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="Schedule",
     *         @OA\JsonContent(ref="#/components/schemas/Schedule")
     *     )
     * )
     */
    $app->get('/api/schedule/{userName}', function (Request $request, Response $response, array $args): Response {
        $checker = new LimitChecker();
        $schedule = $checker->getUserSchedule($args['userName']);
        $response->getBody()->write(json_encode($schedule));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    });

    /**
     * @OA\Post(
     *     path="/api/limits/users/session/stop/{user}",
     *     summary="Stop user session",
     *     tags={"Limits"},
     *     @OA\Parameter(name="user", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="Result message",
     *         @OA\MediaType(mediaType="text/plain", @OA\Schema(type="string"))
     *     )
     * )
     */
    $app->post('/api/limits/users/session/stop/{user}', function (Request $request, Response $response, array $args): Response {
        $user = $args['user'];
        $forceKill = $request->getHeaderLine('X-Force-Kill') === '1';

        $checker = new LimitChecker();

        if ($forceKill) {
            error_log("Force DELETE session for user: $user");
            $result = $checker->deleteSession($user);
            $status = $result ? 200 : 404;
            $body = $result ? "Session deleted for user: $user" : "No active session to delete for user: $user";
        } else {
            $result = $checker->stopSession($user);
            $status = $result ? 200 : 404;
            $body = $result ? "Session stopped for user: $user" : "No active session found for user: $user";
        }
        $response->getBody()->write($body);
        return $response->withStatus($status)->withHeader('Content-Type', 'text/plain');
    });



    // ----- Group module limits -----
    /**
     * @OA\Get(
     *     path="/api/group-module-limits",
     *     summary="List group module limits",
     *     tags={"GroupModuleLimits"},
     *     @OA\Response(
     *         response=200,
     *         description="List of limits",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/GroupModuleLimit"))
     *     )
     * )
     */
    $app->get('/api/group-module-limits', function (Request $request, Response $response): Response {
        $limits = GroupModuleLimit::all();
        $response->getBody()->write($limits->toJson());
        return $response->withHeader('Content-Type', 'application/json');
    });

    /**
     * @OA\Get(
     *     path="/api/group-module-limits/{id}",
     *     summary="Get group module limit",
     *     tags={"GroupModuleLimits"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Limit",
     *         @OA\JsonContent(ref="#/components/schemas/GroupModuleLimit")
     *     ),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    $app->get('/api/group-module-limits/{id}', function (Request $request, Response $response, array $args): Response {
        $limit = GroupModuleLimit::find($args['id']);
        if (!$limit) {
            return $response->withStatus(404);
        }
        $response->getBody()->write($limit->toJson());
        return $response->withHeader('Content-Type', 'application/json');
    });

    /**
     * @OA\Post(
     *     path="/api/group-module-limits",
     *     summary="Create group module limit",
     *     tags={"GroupModuleLimits"},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/GroupModuleLimit")),
     *     @OA\Response(response=201, description="Created", @OA\JsonContent(ref="#/components/schemas/GroupModuleLimit"))
     * )
     */
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

    /**
     * @OA\Put(
     *     path="/api/group-module-limits/{id}",
     *     summary="Update group module limit",
     *     tags={"GroupModuleLimits"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/GroupModuleLimit")),
     *     @OA\Response(response=200, description="Updated", @OA\JsonContent(ref="#/components/schemas/GroupModuleLimit")),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
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

    /**
     * @OA\Delete(
     *     path="/api/group-module-limits/{id}",
     *     summary="Delete group module limit",
     *     tags={"GroupModuleLimits"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Deleted"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    $app->delete('/api/group-module-limits/{id}', function (Request $request, Response $response, array $args): Response {
        $limit = GroupModuleLimit::find($args['id']);
        if (!$limit) {
            return $response->withStatus(404);
        }
        $limit->delete();
        return $response->withStatus(204);
    });

    // ----- User groups -----
    /**
     * @OA\Get(
     *     path="/api/user-groups",
     *     summary="List user groups",
     *     tags={"UserGroups"},
     *     @OA\Response(
     *         response=200,
     *         description="List of user groups",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/UserGroup"))
     *     )
     * )
     */
    $app->get('/api/user-groups', function (Request $request, Response $response): Response {
        $groups = UserGroup::all();
        $response->getBody()->write($groups->toJson());
        return $response->withHeader('Content-Type', 'application/json');
    });

    /**
     * @OA\Get(
     *     path="/api/user-groups/{userName}",
     *     summary="Get user group",
     *     tags={"UserGroups"},
     *     @OA\Parameter(name="userName", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(
     *         response=200,
     *         description="User group",
     *         @OA\JsonContent(ref="#/components/schemas/UserGroup")
     *     ),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    $app->get('/api/user-groups/{userName}', function (Request $request, Response $response, array $args): Response {
        $group = UserGroup::find($args['userName']); // userName = PK
        if (!$group) {
            return $response->withStatus(404);
        }
        $response->getBody()->write($group->toJson());
        return $response->withHeader('Content-Type', 'application/json');
    });

    /**
     * @OA\Post(
     *     path="/api/user-groups",
     *     summary="Create user group",
     *     tags={"UserGroups"},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/UserGroup")),
     *     @OA\Response(response=201, description="Created", @OA\JsonContent(ref="#/components/schemas/UserGroup"))
     * )
     */
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

    /**
     * @OA\Put(
     *     path="/api/user-groups/{userName}",
     *     summary="Update user group",
     *     tags={"UserGroups"},
     *     @OA\Parameter(name="userName", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/UserGroup")),
     *     @OA\Response(response=200, description="Updated", @OA\JsonContent(ref="#/components/schemas/UserGroup")),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
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

    /**
     * @OA\Delete(
     *     path="/api/user-groups/{userName}",
     *     summary="Delete user group",
     *     tags={"UserGroups"},
     *     @OA\Parameter(name="userName", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=204, description="Deleted"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    $app->delete('/api/user-groups/{userName}', function (Request $request, Response $response, array $args): Response {
        $group = UserGroup::find($args['userName']);
        if (!$group) {
            return $response->withStatus(404);
        }
        $group->delete();
        return $response->withStatus(204);
    });

    // ----- Exception users -----
    /**
     * @OA\Get(
     *     path="/api/exception-users",
     *     summary="List exception users",
     *     tags={"ExceptionUsers"},
     *     @OA\Response(
     *         response=200,
     *         description="List of exception users",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/ExceptionUser"))
     *     )
     * )
     */
    $app->get('/api/exception-users', function (Request $request, Response $response): Response {
        $users = ExceptionUser::all();
        $response->getBody()->write($users->toJson());
        return $response->withHeader('Content-Type', 'application/json');
    });

    /**
     * @OA\Get(
     *     path="/api/exception-users/{id}",
     *     summary="Get exception user",
     *     tags={"ExceptionUsers"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Exception user",
     *         @OA\JsonContent(ref="#/components/schemas/ExceptionUser")
     *     ),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    $app->get('/api/exception-users/{id}', function (Request $request, Response $response, array $args): Response {
        $user = ExceptionUser::find($args['id']);
        if (!$user) {
            return $response->withStatus(404);
        }
        $response->getBody()->write($user->toJson());
        return $response->withHeader('Content-Type', 'application/json');
    });

    /**
     * @OA\Post(
     *     path="/api/exception-users",
     *     summary="Create exception user",
     *     tags={"ExceptionUsers"},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/ExceptionUser")),
     *     @OA\Response(response=201, description="Created", @OA\JsonContent(ref="#/components/schemas/ExceptionUser"))
     * )
     */
    $app->post('/api/exception-users', function (Request $request, Response $response): Response {
        $data = json_decode($request->getBody()->getContents(), true);
        $user = new ExceptionUser();
        $user->UserName = $data['UserName'] ?? null;
        $user->save();
        $response->getBody()->write($user->toJson());
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    });

    /**
     * @OA\Put(
     *     path="/api/exception-users/{id}",
     *     summary="Update exception user",
     *     tags={"ExceptionUsers"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/ExceptionUser")),
     *     @OA\Response(response=200, description="Updated", @OA\JsonContent(ref="#/components/schemas/ExceptionUser")),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    $app->put('/api/exception-users/{id}', function (Request $request, Response $response, array $args): Response {
        $user = ExceptionUser::find($args['id']);
        if (!$user) {
            return $response->withStatus(404);
        }
        $data = json_decode($request->getBody()->getContents(), true);
        if (array_key_exists('UserName', $data)) {
            $user->UserName = $data['UserName'];
        }
        $user->save();
        $response->getBody()->write($user->toJson());
        return $response->withHeader('Content-Type', 'application/json');
    });

    /**
     * @OA\Delete(
     *     path="/api/exception-users/{id}",
     *     summary="Delete exception user",
     *     tags={"ExceptionUsers"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Deleted"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    $app->delete('/api/exception-users/{id}', function (Request $request, Response $response, array $args): Response {
        $user = ExceptionUser::find($args['id']);
        if (!$user) {
            return $response->withStatus(404);
        }
        $user->delete();
        return $response->withStatus(204);
    });

    // ----- Swagger docs -----
    $app->get('/swagger.json', function (Request $request, Response $response): Response {
        $openapi = Generator::scan([__DIR__]);
        $response->getBody()->write($openapi->toJson());
        return $response->withHeader('Content-Type', 'application/json');
    });

    $app->get('/docs', function (Request $request, Response $response): Response {
        $html = file_get_contents(__DIR__ . '/../public/swagger.html');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    });
};
