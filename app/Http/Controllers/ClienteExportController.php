<?php

namespace App\Http\Controllers;

use App\Models\Cliente;

class ClienteExportController extends Controller
{
    /**
     * Exportar a CSV simple
     */
    public function exportarSimpleCSV()
    {
        $sucursalId = auth()->user()?->sucursal_id ?? 1;

        // Obtener VENTAS (no clientes)
        $ventas = \App\Models\Venta::where('ventas.sucursal_id', $sucursalId)
            ->with(['cliente', 'vendedor'])
            ->join('clientes', 'ventas.cliente_id', '=', 'clientes.id')
            ->orderBy('clientes.nombre')
            ->select('ventas.*', 'clientes.codigo_anterior')
            ->get();

        $filename = 'clientes_' . date('Y-m-d_His') . '.csv';

        $handle = fopen('php://output', 'w');

        // Configurar cabeceras HTTP
        ob_start();

        // Escribir BOM para UTF-8
        fwrite($handle, "\xEF\xBB\xBF");

        $headers = [
            'Código Anterior',
            'Nombre Completo',
            'DUI',
            'Teléfono Normal',
            'Teléfono WhatsApp',
            'Email',
            'Dirección',
            'Departamento',
            'Ruta de Cobro',
            'Producto',
            'Valor Total',
            'Monto Cobrado',
            'Saldo Pendiente',
            'Estado',
            'Activo',
        ];

        fputcsv($handle, $headers, ';');

        foreach ($ventas as $venta) {
            $cliente = $venta->cliente;
            $producto = $venta->observaciones ?
                (preg_match('/Producto:\s*([^;]+)/', $venta->observaciones, $m) ? trim($m[1]) : '')
                : '';

            $row = [
                $cliente->codigo_anterior,
                $cliente->nombre_completo,
                $cliente->dui ?? '',
                $cliente->telefono_normal ?? '',
                $cliente->telefono_whatsapp ?? '',
                $cliente->email ?? '',
                $cliente->direccion ?? '',
                $cliente->departamento ?? '',
                $cliente->rutaCobro?->nombre ?? '',
                $producto,
                number_format($venta->total, 2, '.', ''),
                number_format($venta->monto_pagado, 2, '.', ''),
                number_format($venta->saldo_pendiente, 2, '.', ''),
                ucfirst($venta->estado),
                $cliente->activo ? 'Sí' : 'No',
            ];
            fputcsv($handle, $row, ';');
        }

        fclose($handle);
        $output = ob_get_clean();

        return response($output, 200, [
            'Content-Type'        => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
