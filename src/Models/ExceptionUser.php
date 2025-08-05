<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExceptionUser extends Model
{
    protected $table = 'ExceptionUsers';
    protected $primaryKey = 'Id';
    public $timestamps = false;
    protected $fillable = ['UserName'];
}
