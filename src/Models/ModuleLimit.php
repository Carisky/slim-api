<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OpenApi\Annotations as OA;

/**
 * Representation of the ModuleLimits configuration table.
 *
 * @OA\Schema(
 *     schema="ModuleLimit",
 *     @OA\Property(property="ModuleName", type="string"),
 *     @OA\Property(property="MaxLicenses", type="integer")
 * )
 */
class ModuleLimit extends Model
{
    protected $table = 'ModuleLimits';

    /** primary key is the module name */
    protected $primaryKey = 'ModuleName';
    public $incrementing = false;

    public $timestamps = false;
    protected $fillable = ['ModuleName', 'MaxLicenses'];
}