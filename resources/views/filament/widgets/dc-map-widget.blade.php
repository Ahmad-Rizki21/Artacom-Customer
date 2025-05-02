{{-- Filament widget for DC map using Mapbox --}}
<div>
    {{-- Single root element untuk Livewire compatibility --}}
    <div class="p-4 bg-white rounded-lg shadow-md">
        <h2 class="text-2xl font-bold text-center mb-1">Sebaran Remote DC di Indonesia</h2>
        <p class="text-gray-500 mb-4 text-center">Lokasi distribution Remote Alfamart dan Lawson PT. Artacomindo Jejaring Nusa</p>
        
        <div class="flex items-center justify-between mb-4">
            <div>
                <button id="dc-btn-all" class="btn px-3 py-1 text-sm rounded bg-gray-800 text-white">Semua</button>
                <button id="dc-btn-alfamart" class="btn px-3 py-1 text-sm rounded bg-blue-100 text-blue-800">Alfamart</button>
                <button id="dc-btn-lawson" class="btn px-3 py-1 text-sm rounded bg-red-100 text-red-800">Lawson</button>
            </div>
            <input id="dc-search-input" type="text" placeholder="Cari DC..." class="px-3 py-1 text-sm border rounded-md">
        </div>

        <div id="dc-map" class="mb-4 rounded-lg border overflow-hidden bg-gray-50" style="height: 500px;"></div>

        <div class="mt-6">
            <h3 class="text-lg font-semibold mb-3">Data Distribution Center</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th class="px-4 py-3">Nama DC</th>
                            <th class="px-4 py-3">Tipe</th>
                            <th class="px-4 py-3">Jumlah Remote</th>
                            <th class="px-4 py-3">Latitude</th>
                            <th class="px-4 py-3">Longitude</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dcLocations as $dc)
                            <tr class="bg-white border-b hover:bg-gray-50 dc-table-row"
                                data-type="{{ $dc['type'] }}"
                                data-name="{{ $dc['name'] }}"
                                data-lat="{{ $dc['lat'] }}"
                                data-lng="{{ $dc['lng'] }}">
                                <td class="px-4 py-3">{{ $dc['name'] }}</td>
                                <td class="px-4 py-3">
                                    @if($dc['type'] == 'Alfamart')
                                        <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">{{ $dc['type'] }}</span>
                                    @else
                                        <span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded">{{ $dc['type'] }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">{{ $dc['remote'] }}</span>
                                </td>
                                <td class="px-4 py-3">{{ $dc['lat'] }}</td>
                                <td class="px-4 py-3">{{ $dc['lng'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p id="dc-counter" class="text-sm text-gray-500 mt-2">
                Menampilkan {{ count($dcLocations) }} distribution center
            </p>
        </div>
    </div>

    @pushOnce('styles')
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.6.0/mapbox-gl.css" rel="stylesheet" />
    <style>
        .mapboxgl-popup-content {
            width: 200px !important;
            border-radius: 8px !important;
        }
        .dc-marker-highlight {
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        
        .dc-table-row.active {
            background-color: #f0f9ff !important;
        }

        /* Custom marker styles */
        .marker-alfamart {
            background-color: #1e40af;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            border: 2px solid white;
            cursor: pointer;
        }
        
        .marker-lawson {
            background-color: #dc2626;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            border: 2px solid white;
            cursor: pointer;
        }
    </style>
    @endPushOnce

    @pushOnce('scripts')
    <script src="https://api.mapbox.com/mapbox-gl-js/v2.6.0/mapbox-gl.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initMap();
        });
        
        function initMap() {
            mapboxgl.accessToken = '{{ config("mapbox.access_token") }}';
            
            const map = new mapboxgl.Map({
                container: 'dc-map',
                style: 'mapbox://styles/mapbox/streets-v11',
                center: [118.0149, -2.5489], // Longitude, Latitude
                zoom: 5
            });
            
            // Tambahkan kontrol navigasi
            map.addControl(new mapboxgl.NavigationControl(), 'top-right');
            
            // Tunggu sampai map sudah dimuat
            map.on('load', function() {
                // Dapatkan data DC dari tabel
                const dcTableRows = document.querySelectorAll('.dc-table-row');
                const markers = [];
                const popups = {};
                
                // Fungsi untuk membuat marker
                function createMarker(dc) {
                    const { type, name, lat, lng } = dc.dataset;
                    
                    // Buat elemen div untuk marker
                    const el = document.createElement('div');
                    el.className = `marker-${type.toLowerCase()}`;
                    
                    // Buat popup
                    const popup = new mapboxgl.Popup({ offset: 25 })
                        .setHTML(`
                            <div>
                                <h3 class="font-bold">${name}</h3>
                                <span class="${type === 'Alfamart' ? 'text-blue-800' : 'text-red-800'}">${type}</span>
                            </div>
                        `);
                    
                    // Buat marker
                    const marker = new mapboxgl.Marker(el)
                        .setLngLat([parseFloat(lng), parseFloat(lat)])
                        .setPopup(popup)
                        .addTo(map);
                    
                    // Simpan referensi marker
                    markers.push(marker);
                    popups[name] = { marker, el, type };
                    
                    // Event listener untuk marker
                    marker.getElement().addEventListener('click', function() {
                        highlightTableRow(name);
                    });
                }
                
                // Buat marker untuk setiap DC
                dcTableRows.forEach(createMarker);
                
                // Fungsi untuk highlight baris tabel saat marker diklik
                function highlightTableRow(dcName) {
                    // Reset semua highlight
                    dcTableRows.forEach(row => row.classList.remove('active'));
                    
                    // Highlight baris yang sesuai
                    const row = Array.from(dcTableRows).find(row => row.dataset.name === dcName);
                    if (row) {
                        row.classList.add('active');
                        row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
                
                // Event listener untuk baris tabel
                dcTableRows.forEach(row => {
                    row.addEventListener('click', function() {
                        const { name, lat, lng } = this.dataset;
                        
                        // Reset semua highlight
                        dcTableRows.forEach(r => r.classList.remove('active'));
                        this.classList.add('active');
                        
                        // Fly to marker location
                        map.flyTo({
                            center: [parseFloat(lng), parseFloat(lat)],
                            zoom: 12,
                            essential: true
                        });
                        
                        // Buka popup
                        if (popups[name]) {
                            popups[name].marker.togglePopup();
                        }
                    });
                });
                
                // Filter buttons
                const btnAll = document.getElementById('dc-btn-all');
                const btnAlfamart = document.getElementById('dc-btn-alfamart');
                const btnLawson = document.getElementById('dc-btn-lawson');
                
                btnAll.addEventListener('click', function() {
                    filterMarkers('all');
                    updateButtonStyles(this);
                });
                
                btnAlfamart.addEventListener('click', function() {
                    filterMarkers('Alfamart');
                    updateButtonStyles(this);
                });
                
                btnLawson.addEventListener('click', function() {
                    filterMarkers('Lawson');
                    updateButtonStyles(this);
                });
                
                function filterMarkers(type) {
                    let visibleCount = 0;
                    
                    dcTableRows.forEach(row => {
                        const showRow = type === 'all' || row.dataset.type === type;
                        row.style.display = showRow ? '' : 'none';
                        
                        if (showRow) visibleCount++;
                        
                        // Tampilkan/sembunyikan marker yang sesuai
                        const dcName = row.dataset.name;
                        if (popups[dcName]) {
                            const marker = popups[dcName].marker;
                            const markerEl = popups[dcName].el;
                            
                            if (showRow) {
                                markerEl.style.display = 'block';
                            } else {
                                markerEl.style.display = 'none';
                                marker.setPopup(null); // Tutup popup jika ada
                            }
                        }
                    });
                    
                    // Update counter
                    document.getElementById('dc-counter').textContent = `Menampilkan ${visibleCount} distribution center`;
                }
                
                function updateButtonStyles(activeBtn) {
                    [btnAll, btnAlfamart, btnLawson].forEach(btn => {
                        btn.classList.remove('bg-gray-800', 'text-white');
                        
                        if (btn === btnAll && btn !== activeBtn) {
                            btn.classList.add('bg-gray-100', 'text-gray-800');
                        } else if (btn === btnAlfamart && btn !== activeBtn) {
                            btn.classList.add('bg-blue-100', 'text-blue-800');
                        } else if (btn === btnLawson && btn !== activeBtn) {
                            btn.classList.add('bg-red-100', 'text-red-800');
                        }
                    });
                    
                    activeBtn.classList.add('bg-gray-800', 'text-white');
                }
                
                // Search functionality
                document.getElementById('dc-search-input').addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    let visibleCount = 0;
                    
                    dcTableRows.forEach(row => {
                        const dcName = row.dataset.name.toLowerCase();
                        const showRow = dcName.includes(searchTerm);
                        
                        row.style.display = showRow ? '' : 'none';
                        
                        if (showRow) visibleCount++;
                        
                        // Tampilkan/sembunyikan marker yang sesuai
                        if (popups[row.dataset.name]) {
                            const markerEl = popups[row.dataset.name].el;
                            markerEl.style.display = showRow ? 'block' : 'none';
                        }
                    });
                    
                    // Update counter
                    document.getElementById('dc-counter').textContent = `Menampilkan ${visibleCount} distribution center`;
                });
                
                // Resize event handler untuk responsive map
                window.addEventListener('resize', function() {
                    map.resize();
                });
            });
        }
    </script>
    @endPushOnce
</div>