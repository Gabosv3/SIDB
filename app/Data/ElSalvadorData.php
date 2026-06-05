<?php

namespace App\Data;

class ElSalvadorData
{
    private static ?array $data = null;

    private static function loadData(): array
    {
        if (self::$data === null) {
            $path = storage_path('el-salvador-2024.json');
            if (file_exists($path)) {
                self::$data = json_decode(file_get_contents($path), true);
            } else {
                self::$data = ['departamentos' => []];
            }
        }
        return self::$data;
    }

    public static function departamentos(): array
    {
        $data = self::loadData();
        $result = [];

        foreach ($data['departamentos'] ?? [] as $dept) {
            $result[$dept['nombre']] = $dept['nombre'];
        }

        return $result;
    }

    public static function municipios(string $departamento): array
    {
        $data = self::loadData();
        $result = [];

        foreach ($data['departamentos'] ?? [] as $dept) {
            if ($dept['nombre'] === $departamento) {
                foreach ($dept['municipios'] ?? [] as $mun) {
                    $result[$mun['nombre']] = $mun['nombre'];
                }
                break;
            }
        }

        return $result;
    }

    public static function distritos(string $municipio = ''): array
    {
        $data = self::loadData();
        $result = [];

        foreach ($data['departamentos'] ?? [] as $dept) {
            foreach ($dept['municipios'] ?? [] as $mun) {
                if ($mun['nombre'] === $municipio) {
                    foreach ($mun['distritos'] ?? [] as $distrito) {
                        $result[$distrito] = $distrito;
                    }
                    break 2;
                }
            }
        }

        return $result;
    }
}
