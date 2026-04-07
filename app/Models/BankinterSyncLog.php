<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankinterSyncLog extends Model
{
    protected $table = 'bankinter_sync_logs';

    protected $fillable = [
        'cuenta_alias',
        'fecha_sync',
        'total_filas',
        'procesados',
        'duplicados',
        'errores',
        'ingresos_creados',
        'gastos_creados',
        'archivo',
        'status',
        'error_message',
    ];

    protected $casts = [
        'fecha_sync' => 'datetime',
    ];
}
