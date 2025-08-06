<?php

use OpenApi\Annotations as OA;

/**
 * @OA\OpenApi(
 *     info=@OA\Info(
 *         title="Slim API",
 *         version="1.0"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="UserTimeLeft",
 *     type="object",
 *     @OA\Property(property="user", type="string"),
 *     @OA\Property(property="pc", type="string"),
 *     @OA\Property(property="time_left", type="integer", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="Schedule",
 *     type="object",
 *     additionalProperties=@OA\Schema(type="array", @OA\Items(type="integer"))
 * )
 */
class OpenApiSpec {}
