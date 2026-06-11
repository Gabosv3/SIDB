<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultado - Pagos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; font-family: -apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; }
        .container { max-width: 900px; margin-top: 2rem; }
        .stat-card { text-align: center; padding: 1.5rem; border-radius: 10px; }
        .stat-card.success { background: #dcfce7; border-left: 4px solid #16a34a; }
        .stat-card.error { background: #fee2e2; border-left: 4px solid #dc2626; }
        .stat-value { font-size: 32px; font-weight: 700; margin: 0; }
        .stat-label { font-size: 13px; font-weight: 600; margin-top: 0.5rem; }
        table { font-size: 13px; }
        thead { background: #f9fafb; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">✅ Pagos Procesados</h1>

        {{-- ── ESTADÍSTICAS ─────────────────────────────────── --}}
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="stat-card success">
                    <p class="stat-value">{{ $resultado['pagos_procesados'] }}</p>
                    <p class="stat-label">✅ Pagos OK</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stat-card {{ $resultado['pagos_errores'] > 0 ? 'error' : 'success' }}">
                    <p class="stat-value">{{ $resultado['pagos_errores'] }}</p>
                    <p class="stat-label">❌ Errores</p>
                </div>
            </div>
        </div>

        {{-- ── DETALLE ──────────────────────────────────────────── --}}
        <div class="card" style="box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: none;">
            <div class="card-header" style="background: #333; color: white; font-weight: 600;">Detalle</div>
            <div class="card-body p-0">
                <div style="overflow-x: auto;">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Estado</th>
                                <th>Cliente</th>
                                <th>DUI</th>
                                <th>Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($resultado['detalles'] as $detalle)
                                <tr style="{{ $detalle['estado'] === 'error' ? 'background: #fff5f5;' : '' }}">
                                    <td>
                                        @if($detalle['estado'] === 'ok')
                                            <span class="badge" style="background: #dcfce7; color: #166534;">✅ OK</span>
                                        @else
                                            <span class="badge" style="background: #fee2e2; color: #991b1b;">❌ Error</span>
                                        @endif
                                    </td>
                                    <td><strong>{{ $detalle['cliente'] }}</strong></td>
                                    <td><code>{{ $detalle['dui'] ?? '—' }}</code></td>
                                    <td>
                                        @if($detalle['estado'] === 'error')
                                            <span style="color: #dc2626;">{{ $detalle['motivo'] }}</span>
                                        @else
                                            <span style="color: #16a34a;">{{ $detalle['monto'] }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted p-4">Sin datos</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ── BOTONES ──────────────────────────────────────────── --}}
        <div style="margin-top: 2rem; text-align: center;">
            <a href="{{ route('pagos-ubicacion.index') }}" class="btn btn-primary btn-lg">
                ← Ingresar más pagos
            </a>
            <a href="/" class="btn btn-secondary btn-lg ms-2">
                Volver al inicio
            </a>
        </div>
    </div>
</body>
</html>
