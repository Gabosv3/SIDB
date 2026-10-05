<?php

namespace App\Filament\Pages;

use App\Models\AsistenteFrase;
use Filament\Actions;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class FrasesAsistente extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationLabel = 'Frases del Asistente';
    protected static ?int $navigationSort = 5;
    protected static ?string $title = 'Frases que el asistente no entendió';
    protected string $view = 'filament.pages.frases-asistente';
    protected Width|string|null $maxContentWidth = Width::Full;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-chat-bubble-left-ellipsis';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Sistema';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(AsistenteFrase::query()
                ->with('user')
                ->addSelect(['asistente_frases_no_entendidas.*'])
                ->selectSub(
                    AsistenteFrase::query()
                        ->from('asistente_frases_no_entendidas as f2')
                        ->selectRaw('count(*)')
                        ->whereColumn('f2.frase', 'asistente_frases_no_entendidas.frase'),
                    'veces'
                ))
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('frase')->label('Frase escrita')->searchable()->wrap()->weight('semibold'),
                Tables\Columns\TextColumn::make('veces')->label('Veces repetida')->sortable()->badge(),
                Tables\Columns\TextColumn::make('tipo')
                    ->label('Resultado')
                    ->formatStateUsing(fn (string $state) => $state === 'sin_resultado' ? 'No entendió' : 'Con duda')
                    ->badge()
                    ->color(fn (string $state) => $state === 'sin_resultado' ? 'danger' : 'warning'),
                Tables\Columns\TextColumn::make('pantalla')->label('Pantalla donde estaba')->toggleable(),
                Tables\Columns\TextColumn::make('user.name')->label('Usuario')->default('—')->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipo')
                    ->label('Resultado')
                    ->options(['sin_resultado' => 'No entendió', 'dudosa' => 'Con duda']),
                Tables\Filters\Filter::make('hoy')->label('Hoy')->query(fn ($q) => $q->whereDate('created_at', today())),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()->label('Quitar de la lista'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([25, 50, 100]);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }
}
