@php
    /** @var \App\Forms\Components\MapPicker $schemaComponent */
    $containerPath = $schemaComponent->getContainer()->getStatePath();
    $prefix        = filled($containerPath) ? $containerPath . '.' : '';
    $latPath       = $prefix . $schemaComponent->getLatField();
    $lngPath       = $prefix . $schemaComponent->getLngField();
    $defaultLat    = $schemaComponent->getDefaultLat();
    $defaultLng    = $schemaComponent->getDefaultLng();
    $zoom          = $schemaComponent->getZoom();
    $height        = $schemaComponent->getMapHeight();
    $mapId         = 'leaflet-map-' . md5($latPath . $lngPath);
@endphp

<div class="col-span-full">
    <div
        wire:ignore
        id="{{ $mapId }}"
        class="rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 shadow-sm"
        style="height: {{ $height }}px;"
    ></div>

    <p class="mt-1.5 text-xs text-gray-400 dark:text-gray-500">
        Haz clic en el mapa para situar la ubicaci&#243;n del cliente.
    </p>
</div>

<script>
(function () {
    var MAP_ID      = @json($mapId);
    var LAT_PATH    = @json($latPath);
    var LNG_PATH    = @json($lngPath);
    var DEFAULT_LAT = {{ $defaultLat }};
    var DEFAULT_LNG = {{ $defaultLng }};
    var ZOOM        = {{ $zoom }};

    function boot() {
        if (typeof L === 'undefined') { setTimeout(boot, 150); return; }

        var el = document.getElementById(MAP_ID);
        if (!el || el._mapBooted) return;
        el._mapBooted = true;

        delete L.Icon.Default.prototype._getIconUrl;
        L.Icon.Default.mergeOptions({
            iconUrl:       'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
            iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
            shadowUrl:     'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
        });

        function getComponent() {
            var wireEl = el.closest('[wire\\:id]');
            return (wireEl && window.Livewire) ? window.Livewire.find(wireEl.getAttribute('wire:id')) : null;
        }
        function setVal(path, value) {
            /* Buscar el input por wire:model y disparar evento input
               que es lo que Livewire 3 wire:model escucha */
            var selectors = [
                '[wire\\:model="'        + path + '"]',
                '[wire\\:model\\.live="' + path + '"]',
            ];
            var input = null;
            for (var s = 0; s < selectors.length; s++) {
                input = document.querySelector(selectors[s]);
                if (input) break;
            }
            if (!input) {
                var key = path.split('.').pop();
                input = document.querySelector('[wire\\:model$=".' + key + '"]')
                     || document.querySelector('[wire\\:model="'  + key + '"]');
            }
            if (input) {
                input.value = value;
                input.dispatchEvent(new Event('input',  { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
            /* Actualizar también via $wire como respaldo */
            var c = getComponent();
            if (c) { try { c.$wire.set(path, value); } catch (e) {} }
        }
        function getVal(path) {
            var c = getComponent();
            try { return c ? c.$wire.get(path) : null; } catch (e) { return null; }
        }

        var initLat = parseFloat(getVal(LAT_PATH)) || DEFAULT_LAT;
        var initLng = parseFloat(getVal(LNG_PATH)) || DEFAULT_LNG;

        var map = L.map(MAP_ID).setView([initLat, initLng], ZOOM);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: 'OpenStreetMap contributors',
            maxZoom: 19,
        }).addTo(map);

        /* Usar CircleMarker para evitar dependencia de imágenes del pin */
        var marker = L.circleMarker([initLat, initLng], {
            radius: 10,
            color: '#2563eb',
            fillColor: '#3b82f6',
            fillOpacity: 0.9,
            weight: 2,
        }).addTo(map);

        map.on('click', function (e) {
            marker.setLatLng(e.latlng);
            setVal(LAT_PATH, e.latlng.lat.toFixed(6));
            setVal(LNG_PATH, e.latlng.lng.toFixed(6));
        });

        setTimeout(function () { map.invalidateSize(); }, 300);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    document.addEventListener('livewire:navigated', function () {
        var el = document.getElementById(MAP_ID);
        if (el) { el._mapBooted = false; }
        boot();
    });
})();
</script>

