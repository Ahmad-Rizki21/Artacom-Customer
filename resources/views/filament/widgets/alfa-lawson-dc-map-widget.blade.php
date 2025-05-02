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
            
            {{-- Map container --}}
            <div id="map-container" class="w-full h-64 rounded-lg mb-6 shadow-sm border border-gray-200" style="height: 300px !important;"></div>            
            {{-- Data table --}}
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="py-2 px-4 text-left text-xs text-gray-500 uppercase">Nama DC</th>
                            <th class="py-2 px-4 text-left text-xs text-gray-500 uppercase">Tipe</th>
                            <th class="py-2 px-4 text-left text-xs text-gray-500 uppercase">Remote</th>
                            <th class="py-2 px-4 text-left text-xs text-gray-500 uppercase">Latitude/Longitude</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dcLocations as $location)
                            <tr class="border-b border-gray-200">
                                <td class="py-2 px-4">{{ $location['name'] }}</td>
                                <td class="py-2 px-4">
                                    <span class="px-2 py-1 text-xs text-white rounded-full" style="background-color: {{ $location['type'] === 'Alfamart' ? '#10b981' : '#3b82f6' }}">
                                        {{ $location['type'] }}
                                    </span>
                                </td>
                                <td class="py-2 px-4">{{ $location['remote'] }}</td>
                                <td class="py-2 px-4">{{ $location['lat'] }}, {{ $location['lng'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        
        @pushOnce('scripts')
            {{-- Include Leaflet CSS --}}
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
            
            {{-- Include Leaflet JS --}}
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        @endPushOnce
        
        <script>
            // Variabel global untuk menyimpan instance peta
            let mapInstance = null;
        
            function initMap(locations) {
                // Cek apakah container masih ada di DOM
                const container = document.getElementById('map-container');
                if (!container) return;
                
                // Bersihkan instance peta yang sudah ada (jika ada)
                if (mapInstance) {
                    mapInstance.remove();
                    mapInstance = null;
                }
                
                // Buat instance peta baru
                mapInstance = L.map('map-container').setView([-2.5489, 118.0149], 5);
                
                // Tambahkan layer tile
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(mapInstance);
                
                // Tambahkan markers
                locations.forEach(location => {
                    const markerColor = location.type === 'Alfamart' ? 'green' : 'blue';
                    
                    L.marker([location.lat, location.lng]).addTo(mapInstance)
                      .bindPopup(`<b>${location.name}</b><br>Tipe: ${location.type}<br>Remote: ${location.remote}`);
                });
            }
        
            // Event listener untuk livewire - HANYA SATU
            document.addEventListener('livewire:initialized', () => {
                // Inisialisasi peta ketika komponen dimuat
                initMap(@json($dcLocations));
                
                // Re-inisialisasi peta ketika komponen diperbarui
                Livewire.hook('morph.updated', ({ el }) => {
                    // Pastikan hanya menginisialisasi ulang jika komponen memiliki map-container
                    if (el.querySelector('#map-container')) {
                        initMap(@json($dcLocations));
                    }
                });
            });
        </script>
    </x-filament::section>
</x-filament-widgets::widget>