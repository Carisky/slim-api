<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="UserGroup",
 *     @OA\Property(property="UserName", type="string"),
 *     @OA\Property(property="Group", type="string"),
 *     @OA\Property(property="WindowsUser", type="string", nullable=true)
 * )
 */
class UserGroup extends Model
{
    protected $table = 'UserGroups';
    /** the table uses simple integer id */
    protected $primaryKey = 'UserName';      // указать правильный PK
    public $incrementing = false;            // ключ строковый, не auto-increment
    protected $keyType = 'string';  
    public $timestamps = false;
    protected $fillable = ['UserName', 'Group', 'WindowsUser'];
}
