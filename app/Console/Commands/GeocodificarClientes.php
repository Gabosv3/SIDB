<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class GeocodificarClientes extends Command
{
    /**
     * Nominatim exige máximo 1 petición por segundo. Se deja un margen
     * (1.1s) sobre el mínimo para no rozar el límite.
     */
    private const SEGUNDOS_ENTRE_PETICIONES = 1.1;

    private const USER_AGENT = 'SIDB-Geocoding/1.0 (gabriel503.alegria@gmail.com)';

    protected $signature = 'clientes:geocodificar
                            {--cliente= : ID o código anterior de un cliente específico}
                            {--limit= : Máximo de clientes a procesar en esta corrida}
                            {--force : Vuelve a geocodificar aunque ya tenga colonia/municipio/distrito/departamento/codigo_postal/pais llenos}
                            {--apply : Aplica los cambios. Sin este flag solo se muestra lo que se guardaría (dry-run), sin tocar la base de datos}';

    protected $description = 'Geocodificación inversa (Nominatim/OpenStreetMap) de clientes con latitud/longitud: '
        .'llena colonia, municipio, distrito, departamento, codigo_postal y pais sin sobrescribir valores ya '
        .'existentes (salvo con --force). Corre en modo de prueba por defecto; agrega --apply para guardar.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $force = (bool) $this->option('force');

        $clientes = $this->resolverClientes();

        if ($clientes === null) {
            return self::FAILURE;
        }

        if ($clientes->isEmpty()) {
            $this->info('No hay clientes con latitud/longitud pendientes de geocodificar.');

            return self::SUCCESS;
        }

        $this->info(($apply ? 'APLICANDO CAMBIOS' : 'MODO DE PRUEBA (dry-run, no se modifica nada)')
            .' — geocodificando '.$clientes->count().' cliente(s).');
        $this->newLine();

        $procesados = 0;
        $actualizados = 0;
        $sinCambios = 0;
        $errores = 0;

        foreach ($clientes as $cliente) {
            if ($procesados > 0) {
                usleep((int) (self::SEGUNDOS_ENTRE_PETICIONES * 1_000_000));
            }
            $procesados++;

            $direccion = $this->reverseGeocode((float) $cliente->latitud, (float) $cliente->longitud);

            if ($direccion === null) {
                $errores++;
                $this->warn("#{$cliente->id} {$cliente->nombre_completo} — sin respuesta de Nominatim, se omite.");

                continue;
            }

            $campos = ['colonia', 'municipio', 'distrito', 'departamento', 'codigo_postal', 'pais'];
            $cambios = [];

            foreach ($campos as $campo) {
                $valorNuevo = $direccion[$campo];
                if ($valorNuevo === null) {
                    continue; // Nominatim no trajo el dato: no inventamos nada
                }
                if (! $force && filled($cliente->{$campo})) {
                    continue; // no se sobrescriben datos ya cargados
                }
                if ($cliente->{$campo} !== $valorNuevo) {
                    $cambios[$campo] = $valorNuevo;
                }
            }

            if (empty($cambios)) {
                $sinCambios++;
                $this->line("#{$cliente->id} {$cliente->nombre_completo} — sin cambios.");

                continue;
            }

            $this->line("#{$cliente->id} {$cliente->nombre_completo}:");
            foreach ($cambios as $campo => $valor) {
                $this->line("  {$campo}: ".($cliente->{$campo} ?: '(vacío)')." -> {$valor}");
            }

            if ($apply) {
                $cliente->update($cambios);
                $this->info('  ✔ guardado.');
            }

            $actualizados++;
        }

        $this->newLine();
        $this->comment(sprintf(
            'Procesados: %d. %s: %d. Sin cambios: %d. Errores: %d.',
            $procesados,
            $apply ? 'Actualizados' : 'Con cambios pendientes',
            $actualizados,
            $sinCambios,
            $errores
        ));

        if (! $apply && $actualizados > 0) {
            $this->comment('Nada se modificó. Vuelve a correr el comando agregando --apply para guardar los cambios.');
        }

        return self::SUCCESS;
    }

    private function resolverClientes(): ?\Illuminate\Support\Collection
    {
        if ($clienteParam = $this->option('cliente')) {
            $cliente = is_numeric($clienteParam)
                ? Cliente::find((int) $clienteParam)
                : Cliente::where('codigo_anterior', $clienteParam)->first();

            if (! $cliente) {
                $this->error("No se encontró ningún cliente con ID o código anterior = {$clienteParam}.");

                return null;
            }

            if (! $cliente->latitud || ! $cliente->longitud) {
                $this->error("El cliente #{$cliente->id} no tiene latitud/longitud registrada.");

                return null;
            }

            return collect([$cliente]);
        }

        $query = Cliente::whereNotNull('latitud')->whereNotNull('longitud');

        if (! $this->option('force')) {
            $query->where(function ($q) {
                $q->whereNull('colonia')
                    ->orWhereNull('municipio')
                    ->orWhereNull('distrito')
                    ->orWhereNull('departamento')
                    ->orWhereNull('codigo_postal')
                    ->orWhereNull('pais');
            });
        }

        $query->orderBy('id');

        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        return $query->get();
    }

    /**
     * @return array{colonia: ?string, municipio: ?string, distrito: ?string, departamento: ?string, codigo_postal: ?string, pais: ?string}|null
     */
    private function reverseGeocode(float $lat, float $lon): ?array
    {
        try {
            $response = Http::withHeaders(['User-Agent' => self::USER_AGENT])
                ->timeout(15)
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'format' => 'jsonv2',
                    'lat' => $lat,
                    'lon' => $lon,
                    'zoom' => 18,
                    'addressdetails' => 1,
                    'accept-language' => 'es',
                ]);
        } catch (\Throwable $e) {
            return null;
        }

        if ($response->failed()) {
            return null;
        }

        $address = $response->json('address', []);

        return [
            'colonia' => $address['suburb'] ?? $address['neighbourhood'] ?? $address['residential'] ?? null,
            'municipio' => $address['city'] ?? $address['town'] ?? $address['municipality'] ?? null,
            'distrito' => $address['city_district'] ?? null,
            'departamento' => $address['state'] ?? null,
            'codigo_postal' => $address['postcode'] ?? null,
            'pais' => $address['country'] ?? null,
        ];
    }
}
