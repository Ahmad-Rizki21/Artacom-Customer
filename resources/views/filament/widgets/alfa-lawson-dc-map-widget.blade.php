<x-filament-widgets::widget>
<x-filament::section>
    <div>
        <h2 class="text-xl font-bold mb-4">Sebaran Remote DC di Indonesia</h2>
        <p class="text-sm text-gray-500 mb-4">
            Lokasi distribution Remote Alfamart dan Lawson PT. Artacomindo Jejaring Nusa
        </p>

        {{-- Filter buttons --}}
        <div class="flex flex-wrap gap-2 mb-4">
            <x-filament::button
                type="button"
                :color="$selectedFilter === 'Semua' ? 'primary' : 'gray'"
                wire:click="filterByType('Semua')"
            >
                Semua
            </x-filament::button>
        </div>

        {{-- Search box --}}
        <div class="mb-4">
            <x-filament::input.wrapper>
                <x-filament::input
                    type="search"
                    wire:model.live.debounce.300ms="searchTerm"
                    placeholder="Cari berdasarkan nama, tipe, atau remote"
                />
            </x-filament::input.wrapper>
        </div>

        {{-- Map tabs --}}
        <div class="flex gap-2 mb-4">
            <div class="flex-1">
                <x-filament::tabs>
                    <x-filament::tabs.item
                        :active="$selectedFilter === 'Alfamart'"
                        wire:click="filterByType('Alfamart')"
                    >
                        Alfamart
                    </x-filament::tabs.item>

                    <x-filament::tabs.item
                        :active="$selectedFilter === 'Lawson'"
                        wire:click="filterByType('Lawson')"
                    >
                        Lawson
                    </x-filament::tabs.item>
                </x-filament::tabs>
            </div>
        </div>

        {{-- Map container, pakai key dinamis supaya setiap filter/search berubah, map container di-reset --}}
        <div wire:ignore :key="$selectedFilter . '-' . $searchTerm">
            <div id="map-container" class="w-full rounded-lg mb-6 shadow-sm border border-gray-200" style="height: 400px !important; min-height: 300px;"></div>
        </div>
    </div>

    <style>
        #map-container {
            height: 400px !important;
            width: 100% !important;
            min-height: 300px !important;
            z-index: 1;
        }
        .leaflet-container {
            height: 100%;
            width: 100%;
        }
    </style>

    @pushOnce('scripts')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    @endPushOnce

    <script>
        // Gunakan variable global di window agar tidak terduplikasi
        window.leafletMapWidget = window.leafletMapWidget || {
            map: null,
            markers: [],
        };

        function initMapWidget() {
            setTimeout(function () {
                const el = document.getElementById('map-container');
                if (!el || el.offsetHeight === 0) {
                    // Jika belum siap, coba lagi sebentar lagi
                    setTimeout(initMapWidget, 100);
                    return;
                }

                // Hapus map sebelumnya jika ada
                if (window.leafletMapWidget.map) {
                    window.leafletMapWidget.map.remove();
                    window.leafletMapWidget.map = null;
                }

                window.leafletMapWidget.map = L.map('map-container').setView([-2.5489, 118.0149], 5);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(window.leafletMapWidget.map);

                // Hapus marker lama
                window.leafletMapWidget.markers.forEach(marker => window.leafletMapWidget.map.removeLayer(marker));
                window.leafletMapWidget.markers = [];

                // Tambahkan marker
                const locations = @json($dcLocations);
                locations.forEach(location => {
                    const iconUrl = location.type === 'Alfamart'
                        ? 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-green.png'
                        : 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-blue.png';

                    const icon = L.icon({
                        iconUrl: iconUrl,
                        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
                        iconSize: [25, 41],
                        iconAnchor: [12, 41],
                        popupAnchor: [1, -34],
                        shadowSize: [41, 41]
                    });

                    const marker = L.marker([location.lat, location.lng], { icon: icon })
                        .bindPopup(`
                            <b>${location.name}</b><br>
                            Tipe: ${location.type}<br>
                            Remote: ${location.remote}
                        `);

                    marker.addTo(window.leafletMapWidget.map);
                    window.leafletMapWidget.markers.push(marker);
                });

                // Pastikan sizing benar
                setTimeout(() => {
                    if (window.leafletMapWidget.map) {
                        window.leafletMapWidget.map.invalidateSize();
                    }
                }, 200);
            }, 100);
        }

        document.addEventListener('DOMContentLoaded', initMapWidget);

        document.addEventListener('livewire:load', function() {
            // Setiap Livewire selesai render (misal filter/search/tab berubah), re-init map
            Livewire.hook('message.processed', (message, component) => {
                initMapWidget();
            });
        });

        // Responsif saat browser resize
        window.addEventListener('resize', function() {
            if (window.leafletMapWidget.map) {
                window.leafletMapWidget.map.invalidateSize();
            }
        });
    </script>
</x-filament::section>
</x-filament-widgets::widget>