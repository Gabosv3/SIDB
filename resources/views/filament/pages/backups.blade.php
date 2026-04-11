<x-filament-panels::page>
    @php
        $backups = $this->getBackups();
    @endphp

    {{-- Resumen --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:1.5rem">
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:0.75rem;padding:1rem;box-shadow:0 1px 2px rgba(0,0,0,.05)">
            <p style="font-size:0.75rem;color:#6b7280;font-weight:500;text-transform:uppercase;letter-spacing:.05em">Total backups</p>
            <p style="font-size:1.875rem;font-weight:700;color:#111827;margin-top:0.25rem">{{ count($backups) }}</p>
        </div>
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:0.75rem;padding:1rem;box-shadow:0 1px 2px rgba(0,0,0,.05)">
            <p style="font-size:0.75rem;color:#6b7280;font-weight:500;text-transform:uppercase;letter-spacing:.05em">Último backup</p>
            <p style="font-size:1rem;font-weight:600;color:#111827;margin-top:0.25rem">{{ $backups[0]['date'] ?? 'Sin backups' }}</p>
            @if(isset($backups[0])) <p style="font-size:0.75rem;color:#6b7280">{{ $backups[0]['age'] }}</p> @endif
        </div>
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:0.75rem;padding:1rem;box-shadow:0 1px 2px rgba(0,0,0,.05)">
            <p style="font-size:0.75rem;color:#6b7280;font-weight:500;text-transform:uppercase;letter-spacing:.05em">Espacio usado</p>
            <p style="font-size:1rem;font-weight:600;color:#111827;margin-top:0.25rem">
                {{ collect($backups)->sum('size_bytes') > 0 ? \Illuminate\Support\Number::fileSize(collect($backups)->sum('size_bytes'), precision: 1) : '0 B' }}
            </p>
        </div>
    </div>

    {{-- Tabla de backups --}}
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:0.75rem;overflow:hidden;box-shadow:0 1px 2px rgba(0,0,0,.05)">
        <div style="padding:1rem 1.25rem;border-bottom:1px solid #e5e7eb">
            <h2 style="font-size:1rem;font-weight:600;color:#111827">Archivos de backup</h2>
            <p style="font-size:0.875rem;color:#6b7280;margin-top:0.125rem">Backups almacenados en el servidor local</p>
        </div>

        @if(count($backups) === 0)
            <div style="padding:3rem;text-align:center;color:#6b7280">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:3rem;height:3rem;margin:0 auto 0.75rem;color:#d1d5db">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                </svg>
                <p style="font-weight:500">No hay backups disponibles</p>
                <p style="font-size:0.875rem;margin-top:0.25rem">Haz clic en "Crear Backup Ahora" para generar el primero.</p>
            </div>
        @else
            <table style="width:100%;border-collapse:collapse">
                <thead>
                    <tr style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                        <th style="text-align:left;padding:0.75rem 1.25rem;font-size:0.75rem;font-weight:600;color:#6b7280;text-transform:uppercase">Archivo</th>
                        <th style="text-align:left;padding:0.75rem 1.25rem;font-size:0.75rem;font-weight:600;color:#6b7280;text-transform:uppercase">Tamaño</th>
                        <th style="text-align:left;padding:0.75rem 1.25rem;font-size:0.75rem;font-weight:600;color:#6b7280;text-transform:uppercase">Fecha</th>
                        <th style="text-align:right;padding:0.75rem 1.25rem;font-size:0.75rem;font-weight:600;color:#6b7280;text-transform:uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($backups as $backup)
                        <tr style="border-bottom:1px solid #f3f4f6">
                            <td style="padding:0.875rem 1.25rem">
                                <div style="display:flex;align-items:center;gap:0.625rem">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:1.25rem;height:1.25rem;color:#d97706;flex-shrink:0">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                                    </svg>
                                    <span style="font-size:0.875rem;font-weight:500;color:#111827;font-family:monospace">{{ $backup['name'] }}</span>
                                </div>
                            </td>
                            <td style="padding:0.875rem 1.25rem;font-size:0.875rem;color:#374151">{{ $backup['size'] }}</td>
                            <td style="padding:0.875rem 1.25rem">
                                <p style="font-size:0.875rem;color:#374151">{{ $backup['date'] }}</p>
                                <p style="font-size:0.75rem;color:#9ca3af">{{ $backup['age'] }}</p>
                            </td>
                            <td style="padding:0.875rem 1.25rem;text-align:right">
                                <div style="display:flex;align-items:center;justify-content:flex-end;gap:0.5rem">
                                    <a href="{{ route('filament.administrativo.pages.backups.download', ['path' => base64_encode($backup['path'])]) }}"
                                       style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.375rem 0.75rem;border-radius:0.5rem;background:#f3f4f6;color:#374151;font-size:0.75rem;font-weight:500;text-decoration:none;border:1px solid #e5e7eb"
                                       title="Descargar">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:0.875rem;height:0.875rem">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                        </svg>
                                        Descargar
                                    </a>
                                    <button
                                        wire:click="deleteBackup('{{ addslashes($backup['path']) }}')"
                                        wire:confirm="¿Eliminar este backup permanentemente?"
                                        style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.375rem 0.75rem;border-radius:0.5rem;background:#fef2f2;color:#b91c1c;font-size:0.75rem;font-weight:500;border:1px solid #fecaca;cursor:pointer"
                                        title="Eliminar">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:0.875rem;height:0.875rem">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                        </svg>
                                        Eliminar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</x-filament-panels::page>
