<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserGroup extends Model
{
    protected $table = 'UserGroups';
    /** the table uses simple integer id */
    protected $primaryKey = 'Id';
    public $timestamps = false;
    protected $fillable = ['Group', 'WindowsUser'];
}