<?php

namespace App\Filament\Resources\ClienteResource\Pages;

use App\Filament\Resources\ClienteResource;
use App\Services\ClienteImportService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Storage;

class ImportarClientes extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = ClienteResource::class;

    protected string $view = 'filament.resources.cliente-resource.pages.importar-clientes';

    protected static ?string $title = 'Importar Clientes desde CSV';

    protected static ?string $navigationLabel = 'Importar CSV';

    // ── Estado del componente ─────────────────────────────────────────────────

    public ?array $data       = [];
    public ?array $resultados = null;  // null = aún no se procesó
    public bool   $procesando = false;

    // ── Boot ──────────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $this->form->fill([
            'sucursal_id'       => auth()->user()?->sucursal_id,
            'omitir_duplicados' => true,
        ]);
    }

    // ── Formulario ────────────────────────────────────────────────────────────

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                FileUpload::make('csv_file')
                    ->label('Archivo CSV')
                    ->disk('local')
                    ->directory('csv-imports')
                    ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel', 'application/octet-stream'])
                    ->maxSize(10240) // 10 MB
                    ->required()
                    ->helperText('Usa el separador punto y coma (;). Descarga la plantilla para ver el formato correcto.'),

                Select::make('sucursal_id')
                    ->label('Sucursal destino')
                    ->options(\App\Models\Sucursal::orderBy('nombre')->pluck('nombre', 'id'))
                    ->searchable()
                    ->required()
                    ->helperText('Los clientes y ventas quedarán asignados a esta sucursal.'),

                Toggle::make('omitir_duplicados')
                    ->label('Omitir filas si el Código Anterior ya existe')
                    ->default(true)
                    ->helperText('Recomendado: evita duplicar clientes en reimportaciones.'),
            ])
            ->statePath('data');
    }

    // ── Acción: procesar CSV ──────────────────────────────────────────────────

    public function importar(): void
    {
        $this->form->validate();

        $datos = $this->data;

        /** @var string $archivo */
        $archivo = $datos['csv_file'];
        $rutaAbsoluta = storage_path('app/' . $archivo);

        if (! file_exists($rutaAbsoluta)) {
            Notification::make()
                ->title('No se encontró el archivo subido.')
                ->danger()
                ->send();
            return;
        }

        try {
            $servicio = new ClienteImportService();
            $this->resultados = $servicio->importar(
                rutaArchivo:      $rutaAbsoluta,
                sucursalId:       (int) $datos['sucursal_id'],
                omitirDuplicados: (bool) ($datos['omitir_duplicados'] ?? true),
            );

            $creados  = $this->resultados['creados'];
            $omitidos = $this->resultados['omitidos'];
            $errores  = $this->resultados['errores'];

            if ($errores === 0) {
                Notification::make()
                    ->title("✅ Importación completada: {$creados} creados, {$omitidos} omitidos.")
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title("⚠️ Importación con errores: {$creados} creados, {$omitidos} omitidos, {$errores} errores.")
                    ->warning()
                    ->send();
            }

            // Eliminar archivo temporal
            Storage::disk('local')->delete($archivo);

        } catch (\Throwable $e) {
            Notification::make()
                ->title('Error al procesar el CSV: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    // ── Limpiar y reiniciar ───────────────────────────────────────────────────

    public function reiniciar(): void
    {
        $this->resultados = null;
        $this->form->fill([
            'sucursal_id'       => auth()->user()?->sucursal_id,
            'omitir_duplicados' => true,
        ]);
    }
}
