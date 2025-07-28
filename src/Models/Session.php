<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    /**
     * Table containing Comarch sessions.
     * Schema CDN is included explicitly to mimic the original database.
     */
    protected $table = 'CDN.Sesje';

    /** @var string Primary key column */
    protected $primaryKey = 'SES_SesjaID';

    /** @var bool Indicates if the IDs are auto-incrementing */
    public $incrementing = false;

    /** @var bool Disable Laravel timestamps */
    public $timestamps = false;
}