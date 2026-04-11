<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ConfiguracionSistema extends Model
{
    protected $table = 'configuracion_sistema';

    protected $fillable = [
        'app_name',
        'app_description',
        'logo',
        'logo_dark',
        'favicon',
        'primary_color',
        'telefono',
        'correo_contacto',
        'direccion',
        'horario',
        'facebook_url',
        'instagram_url',
        'whatsapp_number',
        'twitter_url',
        'tiktok_url',
        'youtube_url',
        'footer_texto',
        'copyright_texto',
    ];

    /** Devuelve la única instancia (cacheada 1 hora). */
    public static function instance(): self
    {
        return Cache::remember('configuracion_sistema', 3600, function () {
            return static::firstOrCreate(
                ['id' => 1],
                ['app_name' => config('app.name', 'SIDB')]
            );
        });
    }

    public static function clearCache(): void
    {
        Cache::forget('configuracion_sistema');
    }
}
