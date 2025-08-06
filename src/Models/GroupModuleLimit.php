<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OpenApi\Annotations as OA;

/**
 * Model for per-group module limits by hour.
 *
 * @OA\Schema(
 *     schema="GroupModuleLimit",
 *     @OA\Property(property="Id", type="integer"),
 *     @OA\Property(property="GroupCode", type="string"),
 *     @OA\Property(property="Module", type="string"),
 *     @OA\Property(property="Hour", type="integer"),
 *     @OA\Property(property="MaxLicenses", type="integer")
 * )
 */
class GroupModuleLimit extends Model
{
    protected $table = 'GroupModuleLimits';

    /** the table uses a simple integer id */
    protected $primaryKey = 'Id';

    public $timestamps = false;
    protected $fillable = ['GroupCode', 'Module', 'Hour', 'MaxLicenses'];
}
