<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $table = 'settings';

    protected $fillable = [
        'key',
        'value',
        'description'
    ];

    /**
     * Obtener un setting por su clave
     */
    public static function get($key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Establecer un setting
     */
    public static function set($key, $value, $description = null)
    {
        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'description' => $description
            ]
        );
    }

    /**
     * Verificar si existe un setting
     */
    public static function has($key)
    {
        return self::where('key', $key)->exists();
    }

    /**
     * Obtener la configuración completa de WhatsApp Business API.
     * Lee de la DB (settings) con fallback a config/services.php (.env).
     */
    public static function whatsappToken(): ?string
    {
        return self::get('whatsapp_token') ?: config('services.whatsapp.token');
    }

    public static function whatsappUrl(): string
    {
        $baseUrl = config('services.whatsapp.base_url');
        $version = self::get('whatsapp_api_version') ?: config('services.whatsapp.api_version');
        $phoneId = self::get('whatsapp_phone_id') ?: config('services.whatsapp.phone_id');

        return "{$baseUrl}/{$version}/{$phoneId}/messages";
    }
}
