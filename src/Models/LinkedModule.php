<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Represents a module link used for fallback checks.
 */
class LinkedModule extends Model
{
    protected $table = 'LinkedModules';

    public $timestamps = false;
    protected $fillable = ['GroupCode', 'ModuleName'];
}
