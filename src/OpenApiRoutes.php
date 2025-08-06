<?php

namespace App;

use OpenApi\Annotations as OA;

/**
 * This class contains dummy methods with OpenAPI annotations
 * that describe the application's HTTP endpoints. The methods
 * have no implementation and exist solely for swagger-php to
 * discover routes when generating the specification.
 */
class OpenApiRoutes
{
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
    public function limitsCheck(): void {}

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
    public function limitsUsers(): void {}

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
    public function userSchedule(): void {}

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
    public function stopUserSession(): void {}

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
    public function groupModuleLimitsList(): void {}

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
    public function groupModuleLimitsGet(): void {}

    /**
     * @OA\Post(
     *     path="/api/group-module-limits",
     *     summary="Create group module limit",
     *     tags={"GroupModuleLimits"},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/GroupModuleLimit")),
     *     @OA\Response(response=201, description="Created", @OA\JsonContent(ref="#/components/schemas/GroupModuleLimit"))
     * )
     */
    public function groupModuleLimitsCreate(): void {}

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
    public function groupModuleLimitsUpdate(): void {}

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
    public function groupModuleLimitsDelete(): void {}

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
    public function userGroupsList(): void {}

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
    public function userGroupsGet(): void {}

    /**
     * @OA\Post(
     *     path="/api/user-groups",
     *     summary="Create user group",
     *     tags={"UserGroups"},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/UserGroup")),
     *     @OA\Response(response=201, description="Created", @OA\JsonContent(ref="#/components/schemas/UserGroup"))
     * )
     */
    public function userGroupsCreate(): void {}

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
    public function userGroupsUpdate(): void {}

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
    public function userGroupsDelete(): void {}

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
    public function exceptionUsersList(): void {}

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
    public function exceptionUsersGet(): void {}

    /**
     * @OA\Post(
     *     path="/api/exception-users",
     *     summary="Create exception user",
     *     tags={"ExceptionUsers"},
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/ExceptionUser")),
     *     @OA\Response(response=201, description="Created", @OA\JsonContent(ref="#/components/schemas/ExceptionUser"))
     * )
     */
    public function exceptionUsersCreate(): void {}

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
    public function exceptionUsersUpdate(): void {}

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
    public function exceptionUsersDelete(): void {}
}

