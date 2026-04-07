<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Edificio extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'edificios';

     /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre',
        'clave',
        'codigo_establecimiento',
        'metodo_entrada',
        'tipo_cerradura_principal',
        'tuyalaravel_building_id',
        'mir_activo',
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
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'mir_activo' => 'boolean',
    ];

    public function checklists()
    {
        return $this->hasMany(Checklist::class);
    }

    public function apartamentos()
    {
        return $this->hasMany(Apartamento::class, 'edificio_id');
    }
}
