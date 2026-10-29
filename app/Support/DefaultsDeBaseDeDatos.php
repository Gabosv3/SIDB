<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * Red de seguridad para los formularios: cuando un campo se deja vacío, el
 * formulario/validación entrega `null`, y un `null` explícito ignora el DEFAULT
 * de la base de datos y revienta el INSERT/UPDATE en las columnas NOT NULL
 * ("Column 'impuesto_porcentaje' cannot be null").
 *
 * Antes de guardar cualquier modelo de App\Models, las columnas NOT NULL que
 * tienen DEFAULT en la base y llegan en null reciben ese DEFAULT. Las columnas
 * NOT NULL sin DEFAULT (campos realmente obligatorios) NO se tocan: ahí lo
 * correcto es que el formulario las valide.
 */
final class DefaultsDeBaseDeDatos
{
    /** @var array<string, array<string, array{valor: ?string, expresion: bool}>> */
    private static array $cache = [];

    public static function aplicar(Model $model): void
    {
        if (! str_starts_with($model::class, 'App\\Models\\')) {
            return;
        }

        $atributos = $model->getAttributes();
        $nulos = array_keys(array_filter($atributos, fn ($valor) => $valor === null));

        if ($nulos === []) {
            return;
        }

        $defaults = self::defaultsDe($model);

        foreach ($nulos as $columna) {
            if (! isset($defaults[$columna])) {
                continue;
            }

            ['valor' => $valor, 'expresion' => $expresion] = $defaults[$columna];

            if ($expresion) {
                // p.ej. CURRENT_TIMESTAMP: se quita el atributo para que la BD lo calcule
                // (en un INSERT) o deje la columna como estaba (en un UPDATE).
                unset($model->{$columna});

                continue;
            }

            $model->setAttribute($columna, $valor);
        }
    }

    /**
     * Columnas NOT NULL con DEFAULT de la tabla del modelo.
     *
     * @return array<string, array{valor: ?string, expresion: bool}>
     */
    public static function defaultsDe(Model $model): array
    {
        $clave = $model->getConnectionName().'|'.$model->getTable();

        if (isset(self::$cache[$clave])) {
            return self::$cache[$clave];
        }

        $resultado = [];

        try {
            foreach (Schema::connection($model->getConnectionName())->getColumns($model->getTable()) as $columna) {
                if ($columna['nullable'] || ($columna['auto_increment'] ?? false) || ! empty($columna['generation'])) {
                    continue;
                }

                $default = self::normalizar($columna['default'] ?? null);

                if ($default !== null) {
                    $resultado[$columna['name']] = $default;
                }
            }
        } catch (\Throwable) {
            // Si no se puede leer el esquema, no se interviene: nunca debe romper un guardado.
            $resultado = [];
        }

        return self::$cache[$clave] = $resultado;
    }

    public static function olvidarCache(): void
    {
        self::$cache = [];
    }

    /**
     * MySQL entrega el default tal cual ("0", "credito", "CURRENT_TIMESTAMP"); MariaDB y
     * SQLite lo entregan con comillas ("'credito'") y usan 'NULL' como texto.
     *
     * @return array{valor: ?string, expresion: bool}|null
     */
    private static function normalizar(mixed $default): ?array
    {
        if ($default === null) {
            return null;
        }

        $texto = trim((string) $default);

        if ($texto === '' && $default !== '') {
            return null;
        }

        if (strcasecmp($texto, 'NULL') === 0) {
            return null;
        }

        if (preg_match('/^\'(.*)\'$/s', $texto, $coincidencia)) {
            return ['valor' => str_replace("''", "'", $coincidencia[1]), 'expresion' => false];
        }

        if (str_contains($texto, '(') || preg_match('/^current_timestamp/i', $texto)) {
            return ['valor' => null, 'expresion' => true];
        }

        return ['valor' => $texto, 'expresion' => false];
    }
}
