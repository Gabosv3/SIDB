<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
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
        'apk_version',
        'apk_url',
        'apk_notas',
        'semana1_fecha_ancla',
    ];

    protected $casts = [
        'semana1_fecha_ancla' => 'date',
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

    /**
     * Semana del ciclo quincenal (1 o 2) a la que pertenece la fecha dada, en
     * base a semana1_fecha_ancla. Alternan cada 7 días hacia adelante y hacia
     * atrás desde el ancla. Null si el ciclo no está configurado — en ese caso
     * ninguna ruta se debe filtrar por semana (todas se muestran siempre).
     */
    public function semanaParaFecha(Carbon $fecha): ?int
    {
        if (! $this->semana1_fecha_ancla) {
            return null;
        }

        $ancla = $this->semana1_fecha_ancla->copy()->startOfDay();
        $dia = $fecha->copy()->startOfDay();

        $diffDias = intdiv($dia->timestamp - $ancla->timestamp, 86400);
        $semanaIndex = (int) floor($diffDias / 7);
        $mod = (($semanaIndex % 2) + 2) % 2;

        return $mod === 0 ? 1 : 2;
    }

    /** Semana del ciclo quincenal para hoy. Null si el ciclo no está configurado. */
    public function semanaActual(): ?int
    {
        return $this->semanaParaFecha(today());
    }
}
