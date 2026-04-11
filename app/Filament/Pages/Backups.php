<?php

namespace App\Filament\Pages;

use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;

class Backups extends Page
{
    protected static ?string $navigationLabel = 'Backups';
    protected static ?int    $navigationSort  = 91;
    protected static ?string $title           = 'Gestión de Backups';
    protected string         $view            = 'filament.pages.backups';

    public static function getNavigationIcon(): string | \BackedEnum | null
    {
        return 'heroicon-o-circle-stack';
    }

    public static function getNavigationGroup(): string | \UnitEnum | null
    {
        return 'Sistema';
    }

    protected Width | string | null $maxContentWidth = Width::Full;

    /** Lista de backups existentes */
    public function getBackups(): array
    {
        $disk   = Storage::disk('local');
        $folder = config('backup.backup.name', config('app.name'));
        $files  = collect($disk->files($folder))
            ->filter(fn ($f) => str_ends_with($f, '.zip'))
            ->sortByDesc(fn ($f) => $disk->lastModified($f))
            ->values();

        return $files->map(function ($file) use ($disk) {
            return [
                'name'         => basename($file),
                'path'         => $file,
                'size'         => Number::fileSize($disk->size($file), precision: 1),
                'size_bytes'   => $disk->size($file),
                'date'         => Carbon::createFromTimestamp($disk->lastModified($file))->format('d/m/Y H:i'),
                'age'          => Carbon::createFromTimestamp($disk->lastModified($file))->diffForHumans(),
            ];
        })->toArray();
    }

    // ── Acciones del header ───────────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [
            Action::make('crear_backup')
                ->label('Crear Backup Ahora')
                ->icon('heroicon-m-plus-circle')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('¿Crear un nuevo backup?')
                ->modalDescription('Esto ejecutará un backup completo de la base de datos y los archivos. Puede tardar unos minutos.')
                ->modalSubmitActionLabel('Sí, crear backup')
                ->action(function () {
                    try {
                        Artisan::call('backup:run', ['--only-db' => true]);
                        Notification::make()
                            ->title('Backup creado exitosamente')
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Error al crear backup')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('crear_backup_completo')
                ->label('Backup Completo')
                ->icon('heroicon-m-archive-box')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('¿Crear backup completo?')
                ->modalDescription('Incluye base de datos y todos los archivos del proyecto. Puede tardar varios minutos.')
                ->modalSubmitActionLabel('Sí, crear backup completo')
                ->action(function () {
                    try {
                        Artisan::call('backup:run');
                        Notification::make()
                            ->title('Backup completo creado')
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Error al crear backup')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('limpiar_backups')
                ->label('Limpiar Backups Viejos')
                ->icon('heroicon-m-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('¿Limpiar backups antiguos?')
                ->modalDescription('Se eliminarán los backups que superen la política de retención configurada.')
                ->modalSubmitActionLabel('Sí, limpiar')
                ->action(function () {
                    try {
                        Artisan::call('backup:clean');
                        Notification::make()
                            ->title('Limpieza completada')
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Error en limpieza')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    /** Descargar un backup */
    public function downloadBackup(string $path): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return Storage::disk('local')->download($path);
    }

    /** Eliminar un backup individual */
    public function deleteBackup(string $path): void
    {
        Storage::disk('local')->delete($path);
        Notification::make()
            ->title('Backup eliminado')
            ->success()
            ->send();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin');
    }
}
