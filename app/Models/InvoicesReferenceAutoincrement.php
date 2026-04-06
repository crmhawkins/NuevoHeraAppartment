<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoicesReferenceAutoincrement extends Model
{
    use HasFactory,SoftDeletes;

    protected $table = 'invoices_reference';

    protected $fillable = [
        'reference_autoincrement',
        'year',
        'month_num',
      

    ];

    /**
     * Mutaciones de fecha.
     *
     * @var array
     */
    protected $dates = [
        'created_at', 'updated_at', 'deleted_at', 
    ];
}
