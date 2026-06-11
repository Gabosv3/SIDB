<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackupDatabase extends Command
{
    protected $signature   = 'backup:database';
    protected $description = 'Backup the database using PHP PDO (no mysqldump dependency)';

    public function handle(): int
    {
        $backupDir = storage_path('app/backups');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $filename = 'db-' . date('Y-m-d-His') . '.sql';
        $filepath = $backupDir . '/' . $filename;

        $database = env('DB_DATABASE');
        $this->info("Iniciando backup de: {$database}");

        $handle = fopen($filepath, 'w');
        if (!$handle) {
            $this->error("No se pudo crear el archivo: {$filepath}");
            return self::FAILURE;
        }

        try {
            $pdo = DB::getPdo();

            // Encabezado
            fwrite($handle, "-- Backup generado: " . now()->format('Y-m-d H:i:s') . "\n");
            fwrite($handle, "-- Base de datos: {$database}\n\n");
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n");
            fwrite($handle, "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n");
            fwrite($handle, "SET NAMES utf8mb4;\n\n");

            // Obtener todas las tablas
            $tables = $pdo->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);
            $total  = count($tables);
            $this->info("Exportando {$total} tablas...");

            foreach ($tables as $i => $table) {
                $this->line("  [{$i}/{$total}] {$table}");

                // DROP + CREATE TABLE
                fwrite($handle, "-- --------------------------------------------------------\n");
                fwrite($handle, "-- Tabla: `{$table}`\n");
                fwrite($handle, "-- --------------------------------------------------------\n\n");
                fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");

                $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_NUM);
                fwrite($handle, $createStmt[1] . ";\n\n");

                // Datos en lotes de 500 filas
                $stmt  = $pdo->query("SELECT * FROM `{$table}`");
                $batch = [];
                $count = 0;

                while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
                    $values = array_map(function ($val) use ($pdo) {
                        if ($val === null) return 'NULL';
                        return $pdo->quote($val);
                    }, $row);

                    $batch[] = '(' . implode(', ', $values) . ')';
                    $count++;

                    if (count($batch) >= 500) {
                        fwrite($handle, "INSERT INTO `{$table}` VALUES\n" . implode(",\n", $batch) . ";\n");
                        $batch = [];
                    }
                }

                if (!empty($batch)) {
                    fwrite($handle, "INSERT INTO `{$table}` VALUES\n" . implode(",\n", $batch) . ";\n");
                }

                fwrite($handle, "\n");
            }

            fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
            fclose($handle);

            $size = filesize($filepath);
            if ($size === 0) {
                $this->error("El archivo quedó vacío.");
                return self::FAILURE;
            }

            // Comprimir con gzip PHP nativo
            $this->info("Comprimiendo...");
            $gzFile = $filepath . '.gz';
            $gz     = gzopen($gzFile, 'wb9');
            $src    = fopen($filepath, 'rb');
            while (!feof($src)) {
                gzwrite($gz, fread($src, 65536));
            }
            fclose($src);
            gzclose($gz);
            unlink($filepath);

            $gzSize = $this->formatBytes(filesize($gzFile));
            $this->info("✓ Backup creado: {$filename}.gz ({$gzSize})");
            return self::SUCCESS;

        } catch (\Throwable $e) {
            if (is_resource($handle)) fclose($handle);
            if (file_exists($filepath)) unlink($filepath);
            $this->error("Error: " . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function formatBytes(int $bytes): string
    {
        foreach (['B', 'KB', 'MB', 'GB'] as $unit) {
            if ($bytes < 1024) return round($bytes, 2) . ' ' . $unit;
            $bytes /= 1024;
        }
        return round($bytes, 2) . ' GB';
    }
}
