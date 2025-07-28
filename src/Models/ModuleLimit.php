<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Representation of the ModuleLimits configuration table.
 */
class ModuleLimit extends Model
{
    protected $table = 'ModuleLimits';

    /** primary key is the module name */
    protected $primaryKey = 'Module';
    public $incrementing = false;

    public $timestamps = false;
}