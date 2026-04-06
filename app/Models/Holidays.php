<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Holidays extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'holidays';

    /**
     * Atributos asignados en masa.
     *
     * @var array
     */
    protected $fillable = [
        'admin_user_id',
        'quantity',
        'first_period',

    ];

    /**
     * Mutaciones de fecha.
     *
     * @var array
     */
    protected $dates = [
        'created_at', 'updated_at', 'deleted_at',
    ];


    /**
     * Obtener el usuario
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function adminUser()
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }
}
