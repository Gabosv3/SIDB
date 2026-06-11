<x-filament-panels::page>

    @if($this->resultado === null)

        {{-- ── FORMULARIO ──────────────────────────────────────────────────── --}}
        <form wire:submit="procesar">
            {{ $this->form }}

            <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                <x-filament::button type="submit" icon="heroicon-m-arrow-up-tray" size="lg">
                    Procesar Pagos y Ubicaciones
                </x-filament::button>
            </div>
        </form>

    @else

        {{-- ── RESULTADOS ───────────────────────────────────────────────────── --}}
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; margin-bottom: 2rem;">

            {{-- Tarjeta: Pagos procesados --}}
            <div style="background: #dcfce7; border-radius: 10px; padding: 1.5rem; border-left: 4px solid #16a34a;">
                <div style="font-size: 28px; font-weight: 700; color: #16a34a;">{{ $this->resultado['pagos_procesados'] }}</div>
                <div style="font-size: 13px; color: #166534; font-weight: 600;">✅ Pagos Procesados</div>
            </div>

            {{-- Tarjeta: Errores en pagos --}}
            <div style="background: {{ $this->resultado['pagos_errores'] > 0 ? '#fee2e2' : '#f3f4f6' }}; border-radius: 10px; padding: 1.5rem; border-left: 4px solid {{ $this->resultado['pagos_errores'] > 0 ? '#dc2626' : '#9ca3af' }};">
                <div style="font-size: 28px; font-weight: 700; color: {{ $this->resultado['pagos_errores'] > 0 ? '#dc2626' : '#9ca3af' }};">{{ $this->resultado['pagos_errores'] }}</div>
                <div style="font-size: 13px; color: {{ $this->resultado['pagos_errores'] > 0 ? '#991b1b' : '#6b7280' }}; font-weight: 600;">❌ Errores en Pagos</div>
            </div>

            {{-- Tarjeta: Ubicaciones actualizadas --}}
            <div style="background: #dbeafe; border-radius: 10px; padding: 1.5rem; border-left: 4px solid #0284c7;">
                <div style="font-size: 28px; font-weight: 700; color: #0284c7;">{{ $this->resultado['ubicaciones_actualizadas'] }}</div>
                <div style="font-size: 13px; color: #0c4a6e; font-weight: 600;">📍 Ubicaciones Actualizadas</div>
            </div>

            {{-- Tarjeta: Errores en ubicaciones --}}
            <div style="background: {{ $this->resultado['ubicaciones_errores'] > 0 ? '#fee2e2' : '#f3f4f6' }}; border-radius: 10px; padding: 1.5rem; border-left: 4px solid {{ $this->resultado['ubicaciones_errores'] > 0 ? '#dc2626' : '#9ca3af' }};">
                <div style="font-size: 28px; font-weight: 700; color: {{ $this->resultado['ubicaciones_errores'] > 0 ? '#dc2626' : '#9ca3af' }};">{{ $this->resultado['ubicaciones_errores'] }}</div>
                <div style="font-size: 13px; color: {{ $this->resultado['ubicaciones_errores'] > 0 ? '#991b1b' : '#6b7280' }}; font-weight: 600;">❌ Errores en Ubicaciones</div>
            </div>

        </div>

        {{-- ── DETALLE ──────────────────────────────────────────────────────── --}}
        <x-filament::section>
            <x-slot name="heading">Detalle del procesamiento</x-slot>

            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <thead>
                        <tr style="background: #f9fafb;">
                            <th style="padding: 0.75rem; text-align: left; font-size: 11px; text-transform: uppercase; color: #6b7280; border-bottom: 1px solid #e5e7eb;">Tipo</th>
                            <th style="padding: 0.75rem; text-align: left; font-size: 11px; text-transform: uppercase; color: #6b7280; border-bottom: 1px solid #e5e7eb;">Estado</th>
                            <th style="padding: 0.75rem; text-align: left; font-size: 11px; text-transform: uppercase; color: #6b7280; border-bottom: 1px solid #e5e7eb;">Cliente</th>
                            <th style="padding: 0.75rem; text-align: left; font-size: 11px; text-transform: uppercase; color: #6b7280; border-bottom: 1px solid #e5e7eb;">DUI</th>
                            <th style="padding: 0.75rem; text-align: left; font-size: 11px; text-transform: uppercase; color: #6b7280; border-bottom: 1px solid #e5e7eb;">Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->resultado['detalles'] as $detalle)
                            <tr style="border-bottom: 1px solid #f3f4f6; {{ $detalle['estado'] === 'error' ? 'background: #fff5f5;' : '' }}">
                                <td style="padding: 0.75rem;">
                                    @if($detalle['tipo'] === 'pago')
                                        <span style="background: #dcfce7; color: #166534; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;">💰 Pago</span>
                                    @else
                                        <span style="background: #dbeafe; color: #0c4a6e; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;">📍 Ubicación</span>
                                    @endif
                                </td>
                                <td style="padding: 0.75rem;">
                                    @if($detalle['estado'] === 'ok')
                                        <span style="background: #dcfce7; color: #166534; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;">✅ OK</span>
                                    @else
                                        <span style="background: #fee2e2; color: #991b1b; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;">❌ Error</span>
                                    @endif
                                </td>
                                <td style="padding: 0.75rem; font-weight: 500;">{{ $detalle['cliente'] }}</td>
                                <td style="padding: 0.75rem; color: #6b7280; font-family: monospace; font-size: 12px;">{{ $detalle['dui'] }}</td>
                                <td style="padding: 0.75rem; color: {{ $detalle['estado'] === 'error' ? '#dc2626' : '#6b7280' }};">
                                    {{ $detalle['monto'] ?? $detalle['coords'] ?? $detalle['motivo'] ?? '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" style="padding: 1rem; text-align: center; color: #9ca3af;">Sin datos</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        {{-- ── BOTONES ──────────────────────────────────────────────────────── --}}
        <div style="margin-top: 1.5rem; display: flex; gap: 1rem;">
            <x-filament::button wire:click="reiniciar" color="gray" icon="heroicon-m-arrow-path">
                Procesar otra tanda
            </x-filament::button>
        </div>

    @endif

</x-filament-panels::page>
