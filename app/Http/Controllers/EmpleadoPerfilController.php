<?php

namespace App\Http\Controllers;

use App\Models\AsignacionDiaria;
use App\Models\Cobrador;
use App\Models\ConfiguracionSistema;
use App\Models\EmployeeDocument;
use App\Models\EmployeePago;
use App\Models\EmployeeProfile;
use App\Models\PagoVenta;
use App\Models\PosDevice;
use App\Models\Supervisor;
use App\Models\User;
use App\Models\Vendedor;
use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmpleadoPerfilController extends Controller
{
    public function show(Request $request, $tenant, $user)
    {
        $empleado = User::query()->findOrFail($user);

        $perfilLaboral = $empleado->vendedor ?? $empleado->cobrador;
        $employeeProfile = $empleado->employeeProfile;
        $tipoPerfil = $this->tipoPerfil($empleado);
        $posDevice = PosDevice::where('user_id', $empleado->id)->latest('ultimo_ping')->first();
        $documentos = EmployeeDocument::where('user_id', $empleado->id)->latest()->get();
        $actividad = $this->actividadReciente($empleado, $employeeProfile);
        $historial = $this->historialPerfil($employeeProfile);
        $asignacionHoy = $empleado->vendedor?->asignacionHoy();

        $ventasMes = $this->ventasMes($empleado);
        $cobrosMes = $this->cobrosMes($empleado);
        $clientesAsignados = $empleado->cobrador?->clientes()->count() ?? 0;
        $clientesActivos = $empleado->cobrador
            ? $empleado->cobrador->clientes()->where('activo', true)->count()
            : 0;

        $metaVentasPct = $employeeProfile?->meta_ventas_mensual > 0
            ? min(100, round(($ventasMes / $employeeProfile->meta_ventas_mensual) * 100))
            : null;
        $metaCobrosPct = $employeeProfile?->meta_cobros_mensual > 0
            ? min(100, round(($cobrosMes / $employeeProfile->meta_cobros_mensual) * 100))
            : null;

        $supervisor = $employeeProfile?->supervisor;
        $ventasSemana = $this->serieUltimosDias(fn ($dia) => $empleado->vendedor
            ? (float) Venta::where('vendedor_id', $empleado->vendedor->id)->whereDate('fecha_venta', $dia)->sum('total')
            : 0.0);
        $cobrosSemana = $this->serieUltimosDias(fn ($dia) => (float) PagoVenta::where('user_id', $empleado->id)->whereDate('fecha_pago', $dia)->whereNull('anulado_en')->sum('monto'));

        // Solo aplica si este empleado es supervisor — se muestra en la pestaña
        // Laboral para que asignar rutas supervisadas no requiera ir a un
        // módulo aparte (antes solo existía dentro de SupervisorResource).
        $rutasCobro = \App\Models\RutaCobro::orderBy('nombre')->get(['id', 'nombre']);
        $rutasSupervisadasIds = $empleado->supervisor?->rutasSupervisadas()->pluck('rutas_cobro.id')->all() ?? [];

        $pagosEmpleado = EmployeePago::where('user_id', $empleado->id)->orderByDesc('fecha_pago')->get();

        return view('empleados.perfil', compact(
            'tenant', 'empleado', 'perfilLaboral', 'employeeProfile', 'tipoPerfil', 'posDevice',
            'documentos', 'actividad', 'historial', 'asignacionHoy', 'ventasMes', 'cobrosMes',
            'clientesAsignados', 'clientesActivos', 'metaVentasPct', 'metaCobrosPct', 'supervisor',
            'ventasSemana', 'cobrosSemana', 'rutasCobro', 'rutasSupervisadasIds', 'pagosEmpleado'
        ));
    }

    public function actualizarPersonal(Request $request, $tenant, $user)
    {
        $empleado = User::query()->findOrFail($user);

        $data = $request->validate([
            'foto' => ['nullable', 'image', 'max:4096'],
            'dui' => ['nullable', 'string', 'max:10'],
            'nit' => ['nullable', 'string', 'max:20'],
            'fecha_nacimiento' => ['nullable', 'date', 'before_or_equal:today'],
            'genero' => ['nullable', 'in:masculino,femenino,otro'],
            'estado_civil' => ['nullable', 'in:soltero,casado,divorciado,viudo,acompanado'],
            'tipo_sangre' => ['nullable', 'string', 'max:5'],
            'telefono_whatsapp' => ['nullable', 'string', 'max:20'],
            'direccion' => ['nullable', 'string', 'max:500'],
            'departamento' => ['nullable', 'string', 'max:100'],
            'municipio' => ['nullable', 'string', 'max:100'],
            'nacionalidad' => ['nullable', 'string', 'max:100'],
            'numero_afiliacion' => ['nullable', 'string', 'max:50'],
            'contacto_emergencia_nombre' => ['nullable', 'string', 'max:150'],
            'contacto_emergencia_telefono' => ['nullable', 'string', 'max:20'],
        ]);

        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('empleados/fotos', 'public');
        } else {
            unset($data['foto']);
        }

        EmployeeProfile::updateOrCreate(['user_id' => $empleado->id], $data);

        return redirect()->back()->with('success', 'Información personal actualizada.');
    }

    public function actualizarLaboral(Request $request, $tenant, $user)
    {
        $empleado = User::query()->findOrFail($user);

        $data = $request->validate([
            'cargo' => ['nullable', 'string', 'max:150'],
            'tipo_empleado' => ['nullable', 'array'],
            'tipo_empleado.*' => ['in:vendedor,cobrador,supervisor'],
            'fecha_ingreso' => ['nullable', 'date'],
            'fecha_salida' => ['nullable', 'date'],
            'salario_base' => ['nullable', 'numeric', 'min:0'],
            'meta_ventas_mensual' => ['nullable', 'numeric', 'min:0'],
            'meta_cobros_mensual' => ['nullable', 'numeric', 'min:0'],
            'tipo_contrato' => ['nullable', 'in:indefinido,temporal,por_obra,practica'],
            'modalidad_pago' => ['nullable', 'in:salario_fijo,comision,mixto'],
            'porcentaje_comision' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'horario_laboral' => ['nullable', 'string', 'max:150'],
            'codigo_asistencia' => ['nullable', 'string', 'max:50', 'unique:employee_profiles,codigo_asistencia,'.$user.',user_id'],
            'hora_entrada_esperada' => ['nullable', 'date_format:H:i'],
            'hora_salida_esperada' => ['nullable', 'date_format:H:i'],
            'estado_laboral' => ['required', 'in:activo,suspendido,inactivo,despedido,renuncia'],
            'supervisor_id' => ['nullable', 'exists:users,id'],
            'puede_usar_pos_movil' => ['nullable', 'boolean'],
            'rutas_supervisadas' => ['nullable', 'array'],
            'rutas_supervisadas.*' => ['integer', 'exists:rutas_cobro,id'],
        ]);

        $data['puede_usar_pos_movil'] = $request->boolean('puede_usar_pos_movil');
        $rutasSupervisadas = $data['rutas_supervisadas'] ?? [];
        unset($data['rutas_supervisadas']);

        EmployeeProfile::updateOrCreate(['user_id' => $empleado->id], $data);

        $this->sincronizarPerfilOperativo($empleado, $data['tipo_empleado'] ?? [], $data['estado_laboral'], $rutasSupervisadas);

        return redirect()->back()->with('success', 'Información laboral actualizada.');
    }

    /**
     * Centraliza en el perfil lo que antes había que ir a hacer a
     * VendedorResource/CobradorResource/SupervisorResource: según los
     * "tipo_empleado" elegidos (una persona puede tener varios a la vez —
     * ej. vendedor y cobrador simultáneamente, como ya soporta el POS
     * móvil), crea (o reactiva) el registro operativo correspondiente. Si
     * un tipo se quita, o su estado laboral ya no es "activo", el registro
     * que ya no aplica se desactiva (nunca se borra — otras tablas lo
     * referencian por FK, como ventas.vendedor_id o rutas_cobro.cobrador_id).
     */
    private function sincronizarPerfilOperativo(User $empleado, array $tiposEmpleado, string $estadoLaboral, array $rutasSupervisadas = []): void
    {
        $partes = preg_split('/\s+/', trim($empleado->name), 2);
        $nombre = $partes[0] ?? $empleado->name;
        $apellido = $partes[1] ?? '';
        $activo = $estadoLaboral === 'activo';

        if (in_array('vendedor', $tiposEmpleado, true)) {
            Vendedor::updateOrCreate(
                ['user_id' => $empleado->id],
                [
                    'nombre' => $nombre,
                    'apellido' => $apellido,
                    'email' => $empleado->email,
                    'activo' => $activo,
                    'codigo' => Vendedor::where('user_id', $empleado->id)->value('codigo') ?? sprintf('V%04d', $empleado->id),
                ]
            );
        } else {
            Vendedor::where('user_id', $empleado->id)->update(['activo' => false]);
        }

        if (in_array('cobrador', $tiposEmpleado, true)) {
            Cobrador::updateOrCreate(
                ['user_id' => $empleado->id],
                [
                    'nombre' => $nombre,
                    'apellido' => $apellido,
                    'email' => $empleado->email,
                    'activo' => $activo,
                    'sucursal_id' => Cobrador::where('user_id', $empleado->id)->value('sucursal_id') ?? \App\Models\Sucursal::first()?->id,
                ]
            );
        } else {
            Cobrador::where('user_id', $empleado->id)->update(['activo' => false]);
        }

        if (in_array('supervisor', $tiposEmpleado, true)) {
            $supervisor = Supervisor::updateOrCreate(
                ['user_id' => $empleado->id],
                ['nombre' => $nombre, 'apellido' => $apellido, 'email' => $empleado->email, 'activo' => $activo]
            );
            $supervisor->rutasSupervisadas()->sync($rutasSupervisadas);
        } else {
            Supervisor::where('user_id', $empleado->id)->update(['activo' => false]);
        }
    }

    public function registrarPago(Request $request, $tenant, $user)
    {
        $empleado = User::query()->findOrFail($user);

        $data = $request->validate([
            'mes_periodo' => ['required', 'string', 'max:100'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'fecha_pago' => ['required', 'date'],
            'metodo_pago' => ['required', 'in:efectivo,transferencia,cheque,deposito'],
            'referencia' => ['nullable', 'string', 'max:150'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ]);

        $data['user_id'] = $empleado->id;
        $data['registrado_por'] = auth()->id();

        EmployeePago::create($data);

        return redirect()->back()->with('success', 'Pago registrado.');
    }

    public function eliminarPago(Request $request, $tenant, $user, EmployeePago $pago)
    {
        if ($pago->user_id !== (int) $user) {
            abort(404);
        }

        $pago->delete();

        return redirect()->back()->with('success', 'Pago eliminado.');
    }

    /**
     * Genera el PDF de "Constancia de Pago" de un pago puntual — mismo
     * formato oficial (logo, datos del colaborador, monto, firmas) que ya
     * se usa en la empresa para dejar constancia de pagos a empleados.
     */
    public function generarConstancia(Request $request, $tenant, $user, EmployeePago $pago)
    {
        if ($pago->user_id !== (int) $user) {
            abort(404);
        }

        $empleado = User::query()->findOrFail($user);
        $config = ConfiguracionSistema::instance();

        $pdf = Pdf::loadView('empleados.constancia-pago-pdf', [
            'empleado' => $empleado,
            'pago' => $pago,
            'config' => $config,
        ])->setPaper('letter', 'portrait');

        return $pdf->stream("constancia-pago-{$empleado->name}-{$pago->fecha_pago->format('Y-m')}.pdf");
    }

    /**
     * Genera el Contrato Individual de Trabajo del empleado — modelo estándar
     * de El Salvador (Código de Trabajo), adaptado según su modalidad de pago
     * (salario fijo / comisión / mixto) y tipo de contrato ya guardados en su
     * perfil laboral.
     */
    public function generarContrato(Request $request, $tenant, $user)
    {
        $empleado = User::query()->findOrFail($user);
        $perfil = $empleado->employeeProfile ?? new EmployeeProfile();
        $config = ConfiguracionSistema::instance();

        $tipoContratoLabel = match ($perfil->tipo_contrato) {
            'indefinido' => 'Tiempo indefinido',
            'temporal' => 'Tiempo determinado',
            'por_obra' => 'Por obra o labor determinada',
            'practica' => 'Práctica / pasantía',
            default => 'No definido',
        };

        $modalidadPagoLabel = match ($perfil->modalidad_pago) {
            'comision' => 'Por comisión',
            'mixto' => 'Mixto (salario + comisión)',
            'salario_fijo' => 'Salario fijo',
            default => 'No definida',
        };

        // Sobre qué se calcula la comisión: vendedores (ventas) y
        // cobradores (cobros) tienen bases distintas, se ajusta el texto
        // legal según el tipo de empleado que ya tiene marcado.
        $tipos = $perfil->tipo_empleado ?? [];
        $comisionBase = in_array('vendedor', $tipos, true) && in_array('cobrador', $tipos, true)
            ? 'las ventas y/o cobros que gestione'
            : (in_array('cobrador', $tipos, true)
                ? 'los cobros que efectivamente recaude'
                : 'las ventas que efectivamente realice');

        $pdf = Pdf::loadView('empleados.contrato-trabajo-pdf', [
            'empleado' => $empleado,
            'perfil' => $perfil,
            'config' => $config,
            'tipoContratoLabel' => $tipoContratoLabel,
            'modalidadPagoLabel' => $modalidadPagoLabel,
            'comisionBase' => $comisionBase,
            'lugar' => $config->direccion ? \Illuminate\Support\Str::before($config->direccion, ',') : 'El Salvador',
            'fechaLetras' => now()->translatedFormat('d \\d\\e F \\d\\e Y'),
        ])->setPaper('letter', 'portrait');

        return $pdf->stream("contrato-trabajo-{$empleado->name}.pdf");
    }

    public function toggleBloqueo(Request $request, $tenant, $user)
    {
        $empleado = User::query()->findOrFail($user);
        $bloqueado = $empleado->estaBloqueado();

        $empleado->update(['account_status' => $bloqueado ? 'activa' : 'bloqueada']);

        if (! $bloqueado) {
            $empleado->cerrarTodasLasSesiones();
        }

        return redirect()->back()->with('success', $bloqueado ? 'Acceso reactivado.' : 'Acceso bloqueado.');
    }

    public function resetearPassword(Request $request, $tenant, $user)
    {
        $empleado = User::query()->findOrFail($user);

        $nueva = Str::password(12);
        $empleado->update(['password' => $nueva]);

        return redirect()->back()->with('password_temporal', $nueva);
    }

    public function cerrarSesiones(Request $request, $tenant, $user)
    {
        $empleado = User::query()->findOrFail($user);
        $empleado->cerrarTodasLasSesiones();

        return redirect()->back()->with('success', 'Sesiones cerradas en todos los dispositivos.');
    }

    public function subirDocumento(Request $request, $tenant, $user)
    {
        $empleado = User::query()->findOrFail($user);

        $data = $request->validate([
            'tipo' => ['required', 'in:dui_frente,dui_reverso,contrato,comprobante_domicilio,licencia,otro'],
            'archivo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:8192'],
        ]);

        $path = $request->file('archivo')->store('empleados/documentos', 'public');

        EmployeeDocument::create([
            'user_id' => $empleado->id,
            'tipo' => $data['tipo'],
            'archivo' => $path,
            'nombre_original' => $request->file('archivo')->getClientOriginalName(),
        ]);

        return redirect()->back()->with('success', 'Documento subido correctamente.');
    }

    public function verificarDocumento(Request $request, $tenant, $user, $documento)
    {
        $data = $request->validate([
            'estado' => ['required', 'in:verificado,rechazado,pendiente'],
        ]);

        $doc = EmployeeDocument::where('user_id', $user)->findOrFail($documento);
        $doc->update([
            'estado_verificacion' => $data['estado'],
            'verificado_por' => auth()->id(),
            'verificado_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Documento actualizado.');
    }

    public function eliminarDocumento(Request $request, $tenant, $user, $documento)
    {
        $doc = EmployeeDocument::where('user_id', $user)->findOrFail($documento);
        Storage::disk('public')->delete($doc->archivo);
        $doc->delete();

        return redirect()->back()->with('success', 'Documento eliminado.');
    }

    public function descargarExpediente(Request $request, $tenant, $user): StreamedResponse
    {
        $empleado = User::query()->findOrFail($user);
        $documentos = EmployeeDocument::where('user_id', $empleado->id)->get();

        $tmpDir = storage_path('app/tmp');
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $zipPath = $tmpDir.'/expediente_'.$empleado->id.'_'.time().'.zip';

        $zip = new \ZipArchive;
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        foreach ($documentos as $doc) {
            $fullPath = Storage::disk('public')->path($doc->archivo);
            if (file_exists($fullPath)) {
                $zip->addFile($fullPath, $doc->tipo.'_'.basename($doc->archivo));
            }
        }
        $zip->close();

        $nombre = $empleado->vendedor?->nombre_completo ?? $empleado->cobrador?->nombre_completo ?? $empleado->name;

        return response()->streamDownload(function () use ($zipPath) {
            readfile($zipPath);
            unlink($zipPath);
        }, 'expediente_'.Str::slug($nombre).'.zip');
    }

    // ── Helpers privados ────────────────────────────────────────────────────

    private function tipoPerfil(User $empleado): ?string
    {
        $esVendedor = (bool) $empleado->vendedor;
        $esCobrador = (bool) $empleado->cobrador;

        return match (true) {
            $esVendedor && $esCobrador => 'vendedor y cobrador',
            $esVendedor => 'vendedor',
            $esCobrador => 'cobrador',
            default => null,
        };
    }

    private function ventasMes(User $empleado): float
    {
        $vendedor = $empleado->vendedor;
        if (! $vendedor) {
            return 0.0;
        }

        return (float) Venta::where('vendedor_id', $vendedor->id)
            ->whereMonth('fecha_venta', now()->month)
            ->whereYear('fecha_venta', now()->year)
            ->sum('total');
    }

    private function cobrosMes(User $empleado): float
    {
        return (float) PagoVenta::where('user_id', $empleado->id)
            ->whereMonth('fecha_pago', now()->month)
            ->whereYear('fecha_pago', now()->year)
            ->whereNull('anulado_en')
            ->sum('monto');
    }

    private function actividadReciente(User $empleado, ?EmployeeProfile $employeeProfile, int $limit = 50)
    {
        $perfilId = $employeeProfile?->id;

        return Activity::query()
            ->where(function ($q) use ($empleado, $perfilId) {
                $q->where(function ($q2) use ($empleado) {
                    $q2->where('causer_type', User::class)->where('causer_id', $empleado->id);
                })->orWhere(function ($q2) use ($empleado) {
                    $q2->where('subject_type', User::class)->where('subject_id', $empleado->id);
                });

                if ($perfilId) {
                    $q->orWhere(function ($q2) use ($perfilId) {
                        $q2->where('subject_type', EmployeeProfile::class)->where('subject_id', $perfilId);
                    });
                }
            })
            ->latest()
            ->limit($limit)
            ->get();
    }

    private function serieUltimosDias(callable $valorPorDia, int $dias = 7): array
    {
        $serie = [];
        for ($i = $dias - 1; $i >= 0; $i--) {
            $serie[] = $valorPorDia(now()->subDays($i)->toDateString());
        }

        return $serie;
    }

    private function historialPerfil(?EmployeeProfile $employeeProfile, int $limit = 50)
    {
        if (! $employeeProfile) {
            return collect();
        }

        return Activity::where('subject_type', EmployeeProfile::class)
            ->where('subject_id', $employeeProfile->id)
            ->latest()
            ->limit($limit)
            ->get();
    }
}
