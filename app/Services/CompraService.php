<?php

namespace App\Services;

use App\Models\Compra;
use App\Models\DetalleCompra;
use App\Models\PagoCompra;
use App\Models\Producto;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class CompraService
{
    /**
     * Crear una nueva compra con sus detalles
     *
     * @param array $data Datos de la compra
     * @param array $detalles Detalles de los artículos
     * @param int|null $usuarioId ID del usuario
     * @return Compra
     */
    public function crearCompra(array $data, array $detalles, ?int $usuarioId = null): Compra
    {
        if (empty($detalles)) {
            throw new InvalidArgumentException('La compra debe tener al menos un artículo');
        }

        $compra = new Compra();
        $compra->numero_compra = Compra::generarNumeroPedido();
        $compra->usuario_id = $usuarioId ?? auth()->id();

        // Asignar datos
        foreach ($data as $key => $value) {
            if (in_array($key, ['subtotal', 'impuesto_monto', 'total'])) {
                continue; // Estos se calculan automáticamente
            }
            $compra->$key = $value;
        }

        $compra->save();

        // Agregar detalles
        foreach ($detalles as $detalle) {
            $this->agregarDetalle($compra, $detalle);
        }

        return $compra;
    }

    /**
     * Agregar un detalle a la compra
     *
     * @param Compra $compra
     * @param array $data
     * @return DetalleCompra
     */
    public function agregarDetalle(Compra $compra, array $data): DetalleCompra
    {
        $detalle = new DetalleCompra($data);
        $detalle->compra_id = $compra->id;
        $detalle->save();

        return $detalle;
    }

    /**
     * Actualizar detalle de compra
     *
     * @param DetalleCompra $detalle
     * @param array $data
     * @return DetalleCompra
     */
    public function actualizarDetalle(DetalleCompra $detalle, array $data): DetalleCompra
    {
        $detalle->update($data);
        return $detalle;
    }

    /**
     * Eliminar detalle de compra
     *
     * @param DetalleCompra $detalle
     * @return bool
     */
    public function eliminarDetalle(DetalleCompra $detalle): bool
    {
        return $detalle->delete();
    }

    /**
     * Registrar pago para una compra
     *
     * @param Compra $compra
     * @param float $monto
     * @param string $formaPago
     * @param string|null $referencia
     * @param string|null $observaciones
     * @param int|null $usuarioId
     * @return PagoCompra
     */
    public function registrarPago(
        Compra $compra,
        float $monto,
        string $formaPago = 'transferencia',
        ?string $referencia = null,
        ?string $observaciones = null,
        ?int $usuarioId = null
    ): PagoCompra {
        if ($monto <= 0) {
            throw new InvalidArgumentException('El monto debe ser mayor a 0');
        }

        if ($monto > $compra->saldo_pendiente) {
            throw new InvalidArgumentException('El monto no puede exceder el saldo pendiente');
        }

        $pago = PagoCompra::create([
            'compra_id' => $compra->id,
            'fecha_pago' => now(),
            'monto' => $monto,
            'forma_pago' => $formaPago,
            'referencia_pago' => $referencia,
            'usuario_id' => $usuarioId ?? auth()->id(),
            'observaciones' => $observaciones,
        ]);

        return $pago;
    }

    /**
     * Obtener recomendaciones de compra basadas en stock mínimo
     *
     * @return Collection
     */
    public function obtenerProductosPorReabastecer(): Collection
    {
        return Producto::where('activo', true)
            ->where('stock', '<=', \DB::raw('stock_minimo'))
            ->with('proveedores')
            ->get();
    }

    /**
     * Obtener costo promedio de un producto
     *
     * @param Producto $producto
     * @param int $periodoMeses
     * @return float
     */
    public function obtenerCostoPromedio(Producto $producto, int $periodoMeses = 3): float
    {
        $fecha = Carbon::now()->subMonths($periodoMeses);

        $promedio = DetalleCompra::where('producto_id', $producto->id)
            ->whereHas('compra', fn ($q) => $q->where('fecha_compra', '>=', $fecha))
            ->avg('precio_unitario');

        return $promedio ?? $producto->precio_compra;
    }

    /**
     * Obtener resumen de compras por proveedor
     *
     * @param int|null $periodoMeses
     * @return Collection
     */
    public function obtenerResumenPorProveedor(?int $periodoMeses = null): Collection
    {
        $query = Compra::query();

        if ($periodoMeses) {
            $fecha = Carbon::now()->subMonths($periodoMeses);
            $query->where('fecha_compra', '>=', $fecha);
        }

        return $query
            ->selectRaw('proveedor_id, COUNT(*) as total_compras, SUM(total) as monto_total')
            ->groupBy('proveedor_id')
            ->with('proveedor')
            ->get();
    }

    /**
     * Obtener deuda total pendiente
     *
     * @return float
     */
    public function obtenerDeudaTotalPendiente(): float
    {
        return Compra::whereIn('estado', ['pendiente', 'parcial'])
            ->sum('saldo_pendiente');
    }

    /**
     * Obtener compras vencidas
     *
     * @return Collection
     */
    public function obtenerComprasVencidas(): Collection
    {
        return Compra::where('fecha_vencimiento', '<', now())
            ->where('saldo_pendiente', '>', 0)
            ->with('proveedor')
            ->get();
    }

    /**
     * Generar reporte de compras
     *
     * @param Carbon|null $fechaInicio
     * @param Carbon|null $fechaFin
     * @return array
     */
    public function generarReporte(?Carbon $fechaInicio = null, ?Carbon $fechaFin = null): array
    {
        $query = Compra::query();

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('fecha_compra', [$fechaInicio, $fechaFin]);
        } elseif ($fechaInicio) {
            $query->where('fecha_compra', '>=', $fechaInicio);
        }

        $compras = $query->get();

        return [
            'total_compras' => $compras->count(),
            'monto_total' => $compras->sum('total'),
            'monto_pagado' => $compras->sum(fn ($c) => $c->total - $c->saldo_pendiente),
            'deuda_pendiente' => $compras->sum('saldo_pendiente'),
            'descuentos_totales' => $compras->sum('descuento_monto'),
            'impuestos_totales' => $compras->sum('impuesto_monto'),
            'compras_por_estado' => $compras->groupBy('estado')->map->count(),
            'compras_por_forma_pago' => $compras->groupBy('forma_pago')->map->count(),
        ];
    }
}
