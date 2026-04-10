<?php

namespace App\Forms\Components;

use Filament\Schemas\Components\Component;

class MapPicker extends Component
{
    protected string $view = 'forms.components.map-picker';

    /** Coordenadas por defecto: San Salvador, El Salvador */
    protected float $defaultLat = 13.6929;
    protected float $defaultLng = -89.2182;
    protected int   $zoom       = 13;
    protected int   $mapHeight  = 360;

    /** Nombre (sin path) del campo latitud en el formulario */
    protected string $latField = 'latitud';

    /** Nombre (sin path) del campo longitud en el formulario */
    protected string $lngField = 'longitud';

    public static function make(): static
    {
        $instance = app(static::class);
        $instance->configure();

        return $instance;
    }

    public function defaultLocation(float $lat, float $lng): static
    {
        $this->defaultLat = $lat;
        $this->defaultLng = $lng;

        return $this;
    }

    public function zoom(int $zoom): static
    {
        $this->zoom = $zoom;

        return $this;
    }

    public function height(int $pixels): static
    {
        $this->mapHeight = $pixels;

        return $this;
    }

    public function latField(string $field): static
    {
        $this->latField = $field;

        return $this;
    }

    public function lngField(string $field): static
    {
        $this->lngField = $field;

        return $this;
    }

    public function getDefaultLat(): float  { return $this->defaultLat; }
    public function getDefaultLng(): float  { return $this->defaultLng; }
    public function getZoom(): int          { return $this->zoom; }
    public function getMapHeight(): int     { return $this->mapHeight; }
    public function getLatField(): string   { return $this->latField; }
    public function getLngField(): string   { return $this->lngField; }
}
