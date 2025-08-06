<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="ExceptionUser",
 *     @OA\Property(property="Id", type="integer"),
 *     @OA\Property(property="UserName", type="string")
 * )
 */
class ExceptionUser extends Model
{
    protected $table = 'ExceptionUsers';
    protected $primaryKey = 'Id';
    public $timestamps = false;
    protected $fillable = ['UserName'];
}
