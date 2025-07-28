<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model for per-group module limits by hour.
 */
class GroupModuleLimit extends Model
{
    protected $table = 'GroupModuleLimits';

    /** the table uses a simple integer id */
    protected $primaryKey = 'Id';

    public $timestamps = false;
}
