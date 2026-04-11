<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Database\Eloquent\Builder;

class AuditLogs extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationLabel = 'Logs de Auditoría';
    protected static ?int $navigationSort = 90;
    protected static ?string $title = 'Logs de Auditoría';
    protected string $view = 'filament.pages.audit-logs';

    public static function getNavigationIcon(): string | \BackedEnum | null
    {
        return 'heroicon-o-clipboard-document-list';
    }

    public static function getNavigationGroup(): string | \UnitEnum | null
    {
        return 'Sistema';
    }

    protected Width | string | null $maxContentWidth = Width::Full;

    public function table(Table $table): Table
    {
        return $table
            ->query(Activity::query()->with('causer')->latest())
            ->columns([
                Tables\Columns\TextColumn::make('log_name')
                    ->label('Módulo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'default' => 'gray',
                        'venta'   => 'success',
                        'compra'  => 'warning',
                        'cliente' => 'info',
                        default   => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Acción')
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        str_contains(strtolower($state), 'created') => 'success',
                        str_contains(strtolower($state), 'updated') => 'warning',
                        str_contains(strtolower($state), 'deleted') => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(function (string $state): string {
                        return match (true) {
                            str_contains(strtolower($state), 'created') => 'Creado',
                            str_contains(strtolower($state), 'updated') => 'Modificado',
                            str_contains(strtolower($state), 'deleted') => 'Eliminado',
                            default => $state,
                        };
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Modelo')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('subject_id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('causer.name')
                    ->label('Usuario')
                    ->default('Sistema')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('description')
                    ->label('Acción')
                    ->options([
                        'created' => 'Creado',
                        'updated' => 'Modificado',
                        'deleted' => 'Eliminado',
                    ])
                    ->query(fn (Builder $query, array $data) =>
                        filled($data['value'])
                            ? $query->where('description', 'like', '%' . $data['value'] . '%')
                            : $query
                    ),

                Tables\Filters\SelectFilter::make('subject_type')
                    ->label('Módulo')
                    ->options([
                        'App\Models\Venta'     => 'Ventas',
                        'App\Models\Compra'    => 'Compras',
                        'App\Models\Producto'  => 'Productos',
                        'App\Models\Cliente'   => 'Clientes',
                        'App\Models\Proveedor' => 'Proveedores',
                    ]),

                Tables\Filters\Filter::make('today')
                    ->label('Hoy')
                    ->query(fn (Builder $query) => $query->whereDate('created_at', today())),
            ])
            ->actions([
                Action::make('ver_cambios')
                    ->label('Ver cambios')
                    ->icon('heroicon-m-eye')
                    ->color('gray')
                    ->modalHeading('Detalle de cambios')
                    ->modalContent(fn (Activity $record): \Illuminate\Contracts\View\View =>
                        view('filament.pages.audit-log-detail', ['activity' => $record])
                    )
                    ->visible(fn (Activity $record): bool => $record->properties->isNotEmpty()),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([25, 50, 100]);
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user?->hasRole('super_admin') || $user?->hasPermissionTo('view_audit_logs');
    }
}
