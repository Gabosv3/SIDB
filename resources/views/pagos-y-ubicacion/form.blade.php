<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingresar Pagos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; font-family: -apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; }
        .container { max-width: 800px; margin-top: 2rem; }
        .card { box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: none; margin-bottom: 2rem; }
        .card-header { background: #25d366; color: white; font-weight: 600; }
        .btn-add { margin-top: 1rem; }
        .row-item { padding: 1rem; background: #f9fafb; border-radius: 8px; margin-bottom: 1rem; border: 1px solid #e5e7eb; }
        .btn-remove { padding: 0.25rem 0.5rem; font-size: 12px; }
        .select2-container--bootstrap-5 .select2-selection { min-height: 38px; }
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered { line-height: 36px; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">💰 Ingresar Pagos Masivos</h1>

        <form method="POST" action="{{ route('pagos-ubicacion.procesar') }}">
            @csrf

            {{-- ── PAGOS ────────────────────────────────────────────── --}}
            <div class="card">
                <div class="card-header">Registrar Pagos</div>
                <div class="card-body">
                    <div id="pagos-container">
                        <div class="row-item">
                            <div class="row">
                                <div class="col-md-5">
                                    <label class="form-label">Cliente</label>
                                    <select class="form-control select-cliente" name="pagos[0][cliente_id]" required>
                                        <option value="">Selecciona un cliente...</option>
                                        @foreach($clientes as $cliente)
                                            <option value="{{ $cliente['id'] }}">{{ $cliente['nombre'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Monto ($)</label>
                                    <input type="number" class="form-control" name="pagos[0][monto_pago]" placeholder="60.00" step="0.01" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Fecha</label>
                                    <input type="date" class="form-control" name="pagos[0][fecha_pago]" value="{{ date('Y-m-d') }}" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Abonos</label>
                                    <input type="number" class="form-control" name="pagos[0][cantidad_abonos]" placeholder="1" value="1" min="1" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-secondary btn-add" onclick="agregarPago()">+ Agregar otro cliente</button>
                </div>
            </div>

            {{-- ── BOTÓN PROCESAR ────────────────────────────────── --}}
            <div style="text-align: center;">
                <button type="submit" class="btn btn-success btn-lg">
                    ✅ Procesar Pagos
                </button>
                <a href="/" class="btn btn-secondary btn-lg ms-2">← Volver</a>
            </div>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        let contadorPagos = 1;

        $(document).ready(function() {
            inicializarSelect();
        });

        function inicializarSelect() {
            $('.select-cliente').select2({
                theme: 'bootstrap-5',
                language: 'es',
                placeholder: 'Busca un cliente...',
                width: '100%',
            });
        }

        function agregarPago() {
            const container = document.getElementById('pagos-container');
            const html = `
                <div class="row-item">
                    <div class="row">
                        <div class="col-md-5">
                            <select class="form-control select-cliente" name="pagos[${contadorPagos}][cliente_id]" required>
                                <option value="">Selecciona un cliente...</option>
                                @foreach($clientes as $cliente)
                                    <option value="{{ $cliente['id'] }}">{{ $cliente['nombre'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="number" class="form-control" name="pagos[${contadorPagos}][monto_pago]" placeholder="60.00" step="0.01" required>
                        </div>
                        <div class="col-md-2">
                            <input type="date" class="form-control" name="pagos[${contadorPagos}][fecha_pago]" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-2">
                            <input type="number" class="form-control" name="pagos[${contadorPagos}][cantidad_abonos]" value="1" min="1" required>
                        </div>
                    </div>
                    <button type="button" class="btn btn-danger btn-sm mt-2" onclick="this.parentElement.remove()">✕ Eliminar</button>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
            inicializarSelect();
            contadorPagos++;
        }
    </script>
</body>
</html>
