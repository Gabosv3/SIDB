<?php

namespace App\Filament\Pages;

use App\Services\AsistenteService;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;

class PalabrasAsistente extends Page
{
    protected static ?string $navigationLabel = 'Palabras del Asistente';
    protected static ?int $navigationSort = 6;
    protected static ?string $title = 'Qué puedes pedirle al asistente';
    protected string $view = 'filament.pages.palabras-asistente';
    protected Width|string|null $maxContentWidth = Width::Full;

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return 'heroicon-o-chat-bubble-bottom-center-text';
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return 'Sistema';
    }

    /** Pantallas con sus palabras y preguntas frecuentes, solo con lo que el usuario actual puede ver. */
    protected function getViewData(): array
    {
        $catalogo = AsistenteService::catalogo();
        $items = collect($catalogo['items']);

        $crear = $items->where('a', 'crear')->keyBy(fn ($i) => implode('|', $i['k']));

        $filas = $items->where('a', '!=', 'crear')->map(function ($i) use ($crear) {
            return [
                'pantalla' => preg_replace('/^Ver /u', '', $i['t']),
                'palabras' => $i['k'],
                'crear' => $crear->has(implode('|', $i['k'])),
                'tipo' => $i['a'],
            ];
        })->sortBy('pantalla', SORT_NATURAL | SORT_FLAG_CASE)->values();

        return [
            'modulos' => $filas->where('tipo', 'ver')->values(),
            'especiales' => $filas->where('tipo', 'pagina')->values(),
            'faq' => $catalogo['faq'],
        ];
    }
}
