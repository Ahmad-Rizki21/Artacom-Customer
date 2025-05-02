// DCMapComponent.jsx
import React, { useState, useEffect } from 'react';
import { MapContainer, TileLayer, Marker, Popup } from 'react-leaflet';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// Fix for default marker icons in React-Leaflet
// Delete these lines if you're using custom marker icons
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://unpkg.com/leaflet@1.7.1/dist/images/marker-icon-2x.png',
  iconUrl: 'https://unpkg.com/leaflet@1.7.1/dist/images/marker-icon.png',
  shadowUrl: 'https://unpkg.com/leaflet@1.7.1/dist/images/marker-shadow.png',
});

const DCMapComponent = ({ locations = [] }) => {
  const [filteredLocations, setFilteredLocations] = useState(locations);
  const [filterType, setFilterType] = useState('all');
  const [searchText, setSearchText] = useState('');

  // Indonesia center coordinates
  const centerPosition = [-2.5489, 118.0149]; // Center of Indonesia
  const zoom = 5;

  // Create custom icon for markers
  const createCustomIcon = (type, remoteCount) => {
    // Define colors based on DC type
    const bgColor = type === 'Alfamart' ? '#3b82f6' : '#ef4444'; // Blue for Alfamart, Red for Lawson
    
    return L.divIcon({
      className: 'custom-div-icon',
      html: `
        <div style="position: relative;">
          <div style="width: 20px; height: 20px; background-color: ${bgColor}; border-radius: 50%; border: 2px solid white;"></div>
          <div style="position: absolute; top: -5px; right: -5px; background-color: #10b981; color: white; font-size: 10px; width: 16px; height: 16px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 1px solid white;">${remoteCount}</div>
        </div>
      `,
      iconSize: [30, 30],
      iconAnchor: [15, 15],
    });
  };

  // Filter locations based on type and search text
  useEffect(() => {
    const filtered = locations.filter((location) => {
      const typeMatch = filterType === 'all' || location.type === filterType;
      const searchMatch = !searchText || location.name.toLowerCase().includes(searchText.toLowerCase());
      return typeMatch && searchMatch;
    });
    
    setFilteredLocations(filtered);
  }, [filterType, searchText, locations]);

  return (
    <div className="p-4 bg-white rounded-lg shadow">
      <h2 className="text-xl font-semibold mb-1">Sebaran Remote DC di Indonesia</h2>
      <p className="text-gray-500 mb-4">Lokasi distribution center Alfamart dan Lawson di Indonesia</p>
      
      {/* Filter Buttons */}
      <div className="flex items-center gap-2 mb-4">
        <button 
          onClick={() => setFilterType('all')}
          className={`px-3 py-1 text-sm font-medium rounded-md ${filterType === 'all' ? 'bg-gray-800 text-white' : 'bg-gray-100 text-gray-800'}`}
        >
          Semua
        </button>
        <button 
          onClick={() => setFilterType('Alfamart')}
          className={`px-3 py-1 text-sm font-medium rounded-md ${filterType === 'Alfamart' ? 'bg-blue-600 text-white' : 'bg-blue-100 text-blue-800'}`}
        >
          Alfamart
        </button>
        <button 
          onClick={() => setFilterType('Lawson')}
          className={`px-3 py-1 text-sm font-medium rounded-md ${filterType === 'Lawson' ? 'bg-red-600 text-white' : 'bg-red-100 text-red-800'}`}
        >
          Lawson
        </button>
        <div className="ml-auto">
          <input 
            type="text" 
            placeholder="Cari DC..." 
            className="px-3 py-1 text-sm border rounded-md"
            value={searchText}
            onChange={(e) => setSearchText(e.target.value)}
          />
        </div>
      </div>
      
      {/* Map Container */}
      <div className="mb-4 rounded-lg border overflow-hidden" style={{ height: '500px' }}>
        <MapContainer 
          center={centerPosition} 
          zoom={zoom} 
          style={{ height: '100%', width: '100%' }}
          scrollWheelZoom={true}
        >
          <TileLayer
            attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
          />
          
          {filteredLocations.map((location, index) => (
            <Marker 
              key={index}
              position={[location.lat, location.lng]}
              icon={createCustomIcon(location.type, location.remote)}
            >
              <Popup>
                <div>
                  <strong>{location.name}</strong><br />
                  Tipe: {location.type}<br />
                  Jumlah Remote: <span style={{
                    backgroundColor: '#10b981',
                    color: 'white',
                    padding: '2px 6px',
                    borderRadius: '10px',
                    fontSize: '12px'
                  }}>{location.remote}</span>
                </div>
              </Popup>
            </Marker>
          ))}
        </MapContainer>
      </div>
      
      {/* Data Table */}
      <div className="mt-6">
        <h3 className="text-lg font-semibold mb-3">Data Distribution Center</h3>
        <div className="overflow-x-auto">
          <table className="w-full text-sm text-left text-gray-700">
            <thead className="text-xs text-gray-700 uppercase bg-gray-50">
              <tr>
                <th className="px-4 py-3">Nama DC</th>
                <th className="px-4 py-3">Tipe</th>
                <th className="px-4 py-3">Jumlah Remote</th>
                <th className="px-4 py-3">Latitude</th>
                <th className="px-4 py-3">Longitude</th>
              </tr>
            </thead>
            <tbody>
              {filteredLocations.map((location, index) => (
                <tr key={index} className="bg-white border-b hover:bg-gray-50">
                  <td className="px-4 py-3">{location.name}</td>
                  <td className="px-4 py-3">
                    {location.type === 'Alfamart' ? (
                      <span className="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">
                        {location.type}
                      </span>
                    ) : (
                      <span className="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded">
                        {location.type}
                      </span>
                    )}
                  </td>
                  <td className="px-4 py-3">
                    <span className="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">
                      {location.remote}
                    </span>
                  </td>
                  <td className="px-4 py-3">{location.lat}</td>
                  <td className="px-4 py-3">{location.lng}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <p className="text-sm text-gray-500 mt-2">
          Menampilkan {filteredLocations.length} dari {locations.length} distribution center
        </p>
      </div>
    </div>
  );
};

export default DCMapComponent;