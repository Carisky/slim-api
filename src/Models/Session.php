<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="Session",
 *     @OA\Property(property="SES_SesjaID", type="integer"),
 *     @OA\Property(property="SES_Komputer", type="string"),
 *     @OA\Property(property="SES_OpeIdent", type="string"),
 *     @OA\Property(property="SES_Modul", type="string"),
 *     @OA\Property(property="SES_Start", type="string", format="date-time"),
 *     @OA\Property(property="SES_Stop", type="integer", nullable=true)
 * )
 */
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