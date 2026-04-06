<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SeoMeta extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'route_name',
        'page_title',
        'meta_description',
        'meta_keywords',
        'og_title',
        'og_description',
        'og_image',
        'og_type',
        'twitter_card',
        'twitter_title',
        'twitter_description',
        'twitter_image',
        'canonical_url',
        'robots',
        'hreflang_es',
        'hreflang_en',
        'hreflang_fr',
        'hreflang_de',
        'hreflang_it',
        'hreflang_pt',
        'structured_data',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'structured_data' => 'array',
    ];

    /**
     * Obtener meta tags por nombre de ruta
     */
    public static function getByRoute($routeName)
    {
        return self::where('route_name', $routeName)
            ->where('active', true)
            ->first();
    }

    /**
     * Obtener o crear meta tags por nombre de ruta
     */
    public static function getOrCreateByRoute($routeName)
    {
        return self::firstOrCreate(
            ['route_name' => $routeName],
            ['active' => true]
        );
    }
}
