<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Token de descarga publico para facturas. Ver migration 2026_04_17_120000.
 */
class InvoiceDownloadToken extends Model
{
    protected $fillable = [
        'invoice_id',
        'token',
        'expires_at',
        'downloaded_at',
        'sent_via',
    ];

    protected $casts = [
        'expires_at'    => 'datetime',
        'downloaded_at' => 'datetime',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoices::class, 'invoice_id');
    }

    public function isValid(): bool
    {
        return $this->expires_at && $this->expires_at->isFuture();
    }
}
