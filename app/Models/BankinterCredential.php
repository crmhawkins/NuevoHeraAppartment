<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * BankinterCredential
 *
 * Credenciales Bankinter almacenadas en BD. La password se cifra en reposo
 * con el cast "encrypted" de Laravel (usa APP_KEY). Estos registros son la
 * fuente principal de verdad para BankinterScraperService; si la tabla esta
 * vacia, el servicio hace fallback a config('services.bankinter.cuentas').
 */
class BankinterCredential extends Model
{
    use HasFactory;

    protected $table = 'bankinter_credentials';

    protected $fillable = [
        'alias',
        'label',
        'user',
        'password',
        'iban',
        'bank_id',
        'enabled',
        'last_sync_at',
    ];

    protected $casts = [
        'password' => 'encrypted',
        'enabled' => 'boolean',
        'last_sync_at' => 'datetime',
    ];

    public function bank()
    {
        return $this->belongsTo(\App\Models\Bancos::class, 'bank_id');
    }
}
