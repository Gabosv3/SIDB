<div class="space-y-4 text-sm">
    @php
        $properties = $activity->properties;
        $old = $properties->get('old', []);
        $attributes = $properties->get('attributes', []);
    @endphp

    @if($old && $attributes)
        {{-- Comparación de cambios --}}
        <table style="width:100%;border-collapse:collapse">
            <thead>
                <tr style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                    <th style="text-align:left;padding:0.5rem 0.75rem;font-size:0.75rem;font-weight:600;color:#6b7280;text-transform:uppercase">Campo</th>
                    <th style="text-align:left;padding:0.5rem 0.75rem;font-size:0.75rem;font-weight:600;color:#ef4444;text-transform:uppercase">Antes</th>
                    <th style="text-align:left;padding:0.5rem 0.75rem;font-size:0.75rem;font-weight:600;color:#16a34a;text-transform:uppercase">Después</th>
                </tr>
            </thead>
            <tbody>
                @foreach($attributes as $key => $newValue)
                    @php $oldValue = $old[$key] ?? '—'; @endphp
                    @if($oldValue != $newValue)
                        <tr style="border-bottom:1px solid #f3f4f6">
                            <td style="padding:0.5rem 0.75rem;font-weight:500;color:#111827;font-family:monospace">{{ $key }}</td>
                            <td style="padding:0.5rem 0.75rem;color:#ef4444">{{ is_array($oldValue) ? json_encode($oldValue) : $oldValue }}</td>
                            <td style="padding:0.5rem 0.75rem;color:#16a34a">{{ is_array($newValue) ? json_encode($newValue) : $newValue }}</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    @elseif($attributes)
        {{-- Creación: solo valores nuevos --}}
        <table style="width:100%;border-collapse:collapse">
            <thead>
                <tr style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                    <th style="text-align:left;padding:0.5rem 0.75rem;font-size:0.75rem;font-weight:600;color:#6b7280;text-transform:uppercase">Campo</th>
                    <th style="text-align:left;padding:0.5rem 0.75rem;font-size:0.75rem;font-weight:600;color:#16a34a;text-transform:uppercase">Valor</th>
                </tr>
            </thead>
            <tbody>
                @foreach($attributes as $key => $value)
                    <tr style="border-bottom:1px solid #f3f4f6">
                        <td style="padding:0.5rem 0.75rem;font-weight:500;color:#111827;font-family:monospace">{{ $key }}</td>
                        <td style="padding:0.5rem 0.75rem;color:#374151">{{ is_array($value) ? json_encode($value) : $value }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p style="color:#6b7280">Sin detalles disponibles.</p>
    @endif
</div>
