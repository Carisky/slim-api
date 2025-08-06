<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OpenApi\Annotations as OA;

/**
 * Represents a module link used for fallback checks.
 *
 * @OA\Schema(
 *     schema="LinkedModule",
 *     @OA\Property(property="GroupCode", type="string"),
 *     @OA\Property(property="ModuleName", type="string")
 * )
 */
class LinkedModule extends Model
{
    protected $table = 'LinkedModules';

    public $timestamps = false;
    protected $fillable = ['GroupCode', 'ModuleName'];
}
