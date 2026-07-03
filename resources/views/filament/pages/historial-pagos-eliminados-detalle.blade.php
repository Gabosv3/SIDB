<div class="space-y-4 text-sm">
    @if($pagos->isNotEmpty())
        <table style="width:100%;border-collapse:collapse">
            <thead>
                <tr style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                    <th style="text-align:left;padding:0.5rem 0.75rem;font-size:0.75rem;font-weight:600;color:#6b7280;text-transform:uppercase">Cliente</th>
                    <th style="text-align:left;padding:0.5rem 0.75rem;font-size:0.75rem;font-weight:600;color:#6b7280;text-transform:uppercase">Venta</th>
                    <th style="text-align:left;padding:0.5rem 0.75rem;font-size:0.75rem;font-weight:600;color:#6b7280;text-transform:uppercase">Método</th>
                    <th style="text-align:left;padding:0.5rem 0.75rem;font-size:0.75rem;font-weight:600;color:#6b7280;text-transform:uppercase">Fecha del pago</th>
                    <th style="text-align:right;padding:0.5rem 0.75rem;font-size:0.75rem;font-weight:600;color:#6b7280;text-transform:uppercase">Monto</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pagos as $pago)
                    <tr style="border-bottom:1px solid #f3f4f6">
                        <td style="padding:0.5rem 0.75rem;font-weight:500;color:#111827">{{ $pago['cliente_nombre'] ?? '—' }}</td>
                        <td style="padding:0.5rem 0.75rem;color:#374151">{{ $pago['numero_venta'] ?? '—' }}</td>
                        <td style="padding:0.5rem 0.75rem;color:#374151">{{ ucfirst($pago['metodo_pago'] ?? '—') }}</td>
                        <td style="padding:0.5rem 0.75rem;color:#374151">{{ $pago['fecha_pago'] ?? '—' }}</td>
                        <td style="padding:0.5rem 0.75rem;text-align:right;font-weight:700;color:#111827">${{ number_format((float) ($pago['monto'] ?? 0), 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p style="color:#6b7280">Sin detalles disponibles.</p>
    @endif
</div>
