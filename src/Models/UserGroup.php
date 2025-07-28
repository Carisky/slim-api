<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserGroup extends Model
{
    protected $table = 'UserGroups';
    public $timestamps = false;
    protected $fillable = ['Group', 'WindowsUser'];
}