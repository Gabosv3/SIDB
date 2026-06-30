<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\PagoVenta;
use App\Models\RutaCobro;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientesRutaController extends Controller
{
    public function index(Request $request, $tenant)
    {
        $rutaId = $request->get('ruta_cobro_id') ?: null;

        $rutas = RutaCobro::withCount('clientes')->orderBy('nombre')->get();

        if (! $rutaId && $rutas->isNotEmpty()) {
            $rutaId = $rutas->first()->id;
        }

        $sinRuta = Cliente::whereNull('ruta_cobro_id')->where('activo', true)->count();

        return view('pos.clientes-ruta', compact('tenant', 'rutas', 'rutaId', 'sinRuta'));
    }

    public function data(Request $request, $tenant): JsonResponse
    {
        $rutaId = $request->get('ruta_cobro_id');
        $buscar = trim((string) $request->get('buscar', ''));

        $query = Cliente::where('activo', true);

        if ($rutaId === 'sin_ruta') {
            $query->whereNull('ruta_cobro_id');
        } elseif ($rutaId !== 'todos') {
            $query->where('ruta_cobro_id', $rutaId);
        }

        if ($buscar !== '') {
            $query->where(function ($q) use ($buscar) {
                $q->where('codigo_anterior', 'like', "%{$buscar}%")
                    ->orWhere('nombre', 'like', "%{$buscar}%")
                    ->orWhere('apellido', 'like', "%{$buscar}%");
            });
        }

        $clientes = $query
            ->with(['rutaCobro:id,nombre', 'ventas' => fn ($q) => $q->where('tipo_pago', 'credito')->oldest('fecha_venta')->with(['pagos' => fn ($p) => $p->oldest('fecha_pago')])])
            ->withCount(['ventas as ventas_count' => fn ($q) => $q->where('saldo_pendiente', '>', 0)])
            ->orderByRaw('orden IS NULL, orden ASC')
            ->orderBy('nombre')
            ->get()
            ->map(function (Cliente $c) {
                $ventasCredito = $c->ventas->map(fn ($v) => [
                    'venta_id' => $v->id,
                    'total' => (float) $v->total,
                    'saldo_pendiente' => (float) $v->saldo_pendiente,
                    'abono_inicial' => $v->pagos->first() ? (float) $v->pagos->first()->monto : null,
                ])->values();

                return [
                    'id' => $c->id,
                    'orden' => $c->orden,
                    'codigo_anterior' => $c->codigo_anterior,
                    'nombre' => $c->nombre_completo,
                    'telefono' => $c->telefono_normal,
                    'direccion' => trim(collect([$c->direccion, $c->municipio, $c->departamento])->filter()->implode(', ')),
                    'direccion_raw' => $c->direccion,
                    'tiene_ubicacion' => (bool) ($c->latitud && $c->longitud),
                    'saldo' => (float) $c->saldo,
                    'ventas_pendientes' => (int) $c->ventas_count,
                    'ruta_cobro_id' => $c->ruta_cobro_id,
                    'ruta_nombre' => $c->rutaCobro?->nombre,
                    'ventas_credito' => $ventasCredito,
                ];
            })
            ->values();

        return response()->json([
            'clientes' => $clientes,
            'total_saldo' => round($clientes->sum('saldo'), 2),
            'total_clientes' => $clientes->count(),
        ]);
    }

    public function reordenar(Request $request, $tenant): JsonResponse
    {
        $data = $request->validate([
            'orden' => 'required|array|min:1',
            'orden.*' => 'required|integer|exists:clientes,id',
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['orden'] as $posicion => $clienteId) {
                Cliente::where('id', $clienteId)->update(['orden' => $posicion + 1]);
            }
        });

        return response()->json(['mensaje' => 'Orden actualizado.']);
    }

    public function cambiarRuta(Request $request, $tenant, Cliente $cliente): JsonResponse
    {
        $data = $request->validate([
            'ruta_cobro_id' => 'nullable|integer|exists:rutas_cobro,id',
        ]);

        $cliente->update([
            'ruta_cobro_id' => $data['ruta_cobro_id'] ?? null,
            'orden' => null,
        ]);

        return response()->json(['mensaje' => 'Cliente actualizado.']);
    }

    public function actualizarAbonoInicial(Request $request, $tenant, Cliente $cliente): JsonResponse
    {
        $data = $request->validate([
            'venta_id' => 'required|integer',
            'monto' => 'required|numeric|min:0',
        ]);

        $venta = $cliente->ventas()->where('tipo_pago', 'credito')->where('id', $data['venta_id'])->first();

        if (! $venta) {
            return response()->json(['mensaje' => 'Esta venta no pertenece a este cliente.'], 422);
        }

        $primerPagoId = $venta->pagos()->oldest('fecha_pago')->value('id');
        $otrosPagos = (float) $venta->pagos()
            ->when($primerPagoId, fn ($q) => $q->where('id', '!=', $primerPagoId))
            ->sum('monto');

        if ($data['monto'] + $otrosPagos > (float) $venta->total) {
            return response()->json(['mensaje' => 'El abono supera el total de la venta ($'.number_format($venta->total, 2).').'], 422);
        }

        DB::transaction(function () use ($venta, $data) {
            $pagoInicial = $venta->pagos()->oldest('fecha_pago')->first();

            if ($pagoInicial) {
                $pagoInicial->update(['monto' => $data['monto']]);
            } else {
                PagoVenta::create([
                    'venta_id' => $venta->id,
                    'cliente_id' => $venta->cliente_id,
                    'user_id' => auth()->id() ?? 1,
                    'monto' => $data['monto'],
                    'fecha_pago' => $venta->fecha_venta->toDateString(),
                    'metodo_pago' => 'efectivo',
                    'observaciones' => 'Saldo inicial importado (cobro en papel)',
                ]);
            }

            $venta->refresh();
            $totalPagado = round((float) $venta->prima + (float) $venta->pagos()->sum('monto'), 2);
            $venta->monto_pagado = $totalPagado;
            $venta->saldo_pendiente = max(0, round($venta->total - $totalPagado, 2));
            $venta->estado = $venta->saldo_pendiente <= 0 ? 'completada' : 'pendiente';
            $venta->save();

            // Redistribuir las cuotas en orden segun el nuevo total pagado (misma lógica FIFO usada al generarlas)
            $restante = $totalPagado;
            foreach ($venta->gestionesCobro()->orderBy('numero_cuota')->get() as $cuota) {
                $montoCuota = (float) $cuota->monto_cuota;
                $pagadoCuota = round(min($restante, $montoCuota), 2);
                $restante = round($restante - $pagadoCuota, 2);

                $estado = 'pendiente';
                if ($pagadoCuota >= $montoCuota) { $estado = 'cobrado'; }
                elseif ($pagadoCuota > 0) { $estado = 'parcialmente_cobrado'; }

                $cuota->update(['monto_pagado' => $pagadoCuota, 'estado' => $estado]);
            }

            $cliente = $venta->cliente;
            $cliente->saldo = round($cliente->ventas()->sum('saldo_pendiente'), 2);
            $cliente->save();
        });

        return response()->json(['mensaje' => 'Abono inicial actualizado.']);
    }

    public function actualizarCampo(Request $request, $tenant, Cliente $cliente): JsonResponse
    {
        $data = $request->validate([
            'campo' => 'required|in:nombre,telefono,direccion,saldo',
            'valor' => 'required',
        ]);

        switch ($data['campo']) {
            case 'nombre':
                $valor = trim((string) $data['valor']);
                if ($valor === '') {
                    return response()->json(['mensaje' => 'El nombre no puede quedar vacío.'], 422);
                }
                $partes = preg_split('/\s+/', $valor, 2);
                $cliente->nombre = $partes[0];
                $cliente->apellido = $partes[1] ?? '';
                break;

            case 'telefono':
                $cliente->telefono_normal = trim((string) $data['valor']);
                break;

            case 'direccion':
                $cliente->direccion = trim((string) $data['valor']);
                break;

            case 'saldo':
                if (! is_numeric($data['valor']) || (float) $data['valor'] < 0) {
                    return response()->json(['mensaje' => 'Saldo inválido.'], 422);
                }
                $cliente->saldo = round((float) $data['valor'], 2);
                break;
        }

        $cliente->save();

        return response()->json(['mensaje' => 'Cliente actualizado.']);
    }
}
