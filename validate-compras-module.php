#!/usr/bin/env php
<?php

/**
 * Script de Validación del Módulo de Compras
 * 
 * Este script verifica que todos los componentes del módulo estén correctamente instalados
 * Uso: php validate-compras-module.php
 */

$checks = [];
$warnings = [];
$errors = [];

echo "\n╔══════════════════════════════════════════════════════════════════╗\n";
echo "║         VALIDACIÓN DEL MÓDULO DE COMPRAS Y PROVEEDORES          ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

// Función para verificar archivos
function checkFile($path, $label) {
    global $checks, $errors;
    if (file_exists($path)) {
        $checks[] = "✓ {$label}";
        return true;
    } else {
        $errors[] = "✗ FALTA: {$label} ({$path})";
        return false;
    }
}

// Función para verificar clases
function checkClass($class, $label) {
    global $checks, $warnings;
    if (class_exists($class)) {
        $checks[] = "✓ {$label}";
        return true;
    } else {
        $warnings[] = "⚠ NO ENCONTRADA: {$label} ({$class})";
        return false;
    }
}

// ──────────────────────────────────────────────────────────────────────────

echo "📁 VERIFICANDO MODELOS...\n";
checkClass('App\Models\Proveedor', 'Modelo Proveedor');
checkClass('App\Models\Compra', 'Modelo Compra');
checkClass('App\Models\DetalleCompra', 'Modelo DetalleCompra');
checkClass('App\Models\PagoCompra', 'Modelo PagoCompra');

echo "\n📁 VERIFICANDO RESOURCES DE FILAMENT...\n";
checkClass('App\Filament\Resources\ProveedorResource', 'ProveedorResource');
checkClass('App\Filament\Resources\CompraResource', 'CompraResource');

echo "\n📁 VERIFICANDO PAGES...\n";
checkClass('App\Filament\Resources\ProveedorResource\Pages\ListProveedores', 'ListProveedores');
checkClass('App\Filament\Resources\ProveedorResource\Pages\CreateProveedor', 'CreateProveedor');
checkClass('App\Filament\Resources\CompraResource\Pages\ListCompras', 'ListCompras');
checkClass('App\Filament\Resources\CompraResource\Pages\CreateCompra', 'CreateCompra');

echo "\n📁 VERIFICANDO POLICIES...\n";
checkClass('App\Policies\ProveedorPolicy', 'ProveedorPolicy');
checkClass('App\Policies\CompraPolicy', 'CompraPolicy');

echo "\n📁 VERIFICANDO OBSERVERS...\n";
checkClass('App\Observers\CompraObserver', 'CompraObserver');
checkClass('App\Observers\DetalleCompraObserver', 'DetalleCompraObserver');
checkClass('App\Observers\PagoCompraObserver', 'PagoCompraObserver');

echo "\n📁 VERIFICANDO SERVICIOS...\n";
checkClass('App\Services\CompraService', 'CompraService');

echo "\n📁 VERIFICANDO FACTORIES...\n";
checkFile('database/factories/ProveedorFactory.php', 'ProveedorFactory');
checkFile('database/factories/CompraFactory.php', 'CompraFactory');
checkFile('database/factories/DetalleCompraFactory.php', 'DetalleCompraFactory');

echo "\n📁 VERIFICANDO SEEDERS...\n";
checkFile('database/seeders/ProveedorSeeder.php', 'ProveedorSeeder');

echo "\n📁 VERIFICANDO WIDGETS...\n";
checkClass('App\Filament\Widgets\ComprasOverviewWidget', 'ComprasOverviewWidget');
checkClass('App\Filament\Widgets\ComprasPorProveedorChart', 'ComprasPorProveedorChart');
checkClass('App\Filament\Widgets\ComprasRecientesWidget', 'ComprasRecientesWidget');

echo "\n📁 VERIFICANDO DOCUMENTACIÓN...\n";
checkFile('COMPRAS_MODULO.md', 'Documentación del Módulo');

// ──────────────────────────────────────────────────────────────────────────

echo "\n" . str_repeat('─', 70) . "\n";

if (!empty($checks)) {
    echo "\n✓ COMPONENTES VERIFICADOS (" . count($checks) . "):\n";
    foreach ($checks as $check) {
        echo "  {$check}\n";
    }
}

if (!empty($warnings)) {
    echo "\n⚠ ADVERTENCIAS (" . count($warnings) . "):\n";
    foreach ($warnings as $warning) {
        echo "  {$warning}\n";
    }
}

if (!empty($errors)) {
    echo "\n✗ ERRORES (" . count($errors) . "):\n";
    foreach ($errors as $error) {
        echo "  {$error}\n";
    }
}

echo "\n" . str_repeat('─', 70) . "\n";

$totalOk = count($checks);
$totalProblemas = count($errors) + count($warnings);

echo "\n📊 RESUMEN:\n";
echo "  ✓ OK: {$totalOk}\n";
echo "  ⚠ Advertencias: " . count($warnings) . "\n";
echo "  ✗ Errores: " . count($errors) . "\n\n";

if (count($errors) === 0) {
    echo "✅ VALIDACIÓN EXITOSA - El módulo está listo para usar\n\n";
    echo "📝 PRÓXIMOS PASOS:\n";
    echo "  1. Ejecutar migraciones: php artisan migrate\n";
    echo "  2. Ejecutar seeders (opcional): php artisan db:seed --class=ProveedorSeeder\n";
    echo "  3. Acceder al panel Filament\n";
    echo "  4. Los nuevos módulos aparecerán en el menú lateral\n\n";
} else {
    echo "❌ POR FAVOR, REVISA LOS ERRORES ANTERIORES ANTES DE CONTINUAR\n\n";
}

echo str_repeat('═', 70) . "\n\n";
