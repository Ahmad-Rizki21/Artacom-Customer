// App.jsx or your entry React file
import React from 'react';
import ReactDOM from 'react-dom/client';
import DCMapComponent from './DCMapComponent';

// This assumes your PHP code passes the JSON data to a global variable
// You can adjust this as needed based on how you want to pass the data
// from your PHP/Blade template to the React component
const dcLocations = window.dcLocations || [
    {
        name: 'DC Alfamart Jakarta',
        lat: -6.2088,
        lng: 106.8456,
        type: 'Alfamart',
        remote: '5',
    },
    {
        name: 'DC Lawson Surabaya',
        lat: -7.2575,
        lng: 112.7521,
        type: 'Lawson',
        remote: '5',
    },
    {
        name: 'DC Alfamart Bandung',
        lat: -6.9175,
        lng: 107.6191,
        type: 'Alfamart',
        remote: '5',
    },
    {
        name: 'DC Lawson Bali',
        lat: -8.4095,
        lng: 115.1889,
        type: 'Lawson',
        remote: '5',
    },
    {
        name: 'DC Alfamart Medan',
        lat: 3.5952,
        lng: 98.6722,
        type: 'Alfamart',
        remote: '5',
    },
    {
        name: 'DC Alfamart Makassar',
        lat: -5.1477,
        lng: 119.4327,
        type: 'Alfamart',
        remote: '5',
    }
];

// Mount the React component to a div with id 'dc-map' in your HTML
document.addEventListener('DOMContentLoaded', function() {
    const mapContainer = document.getElementById('dc-map');
    if (mapContainer) {
        const root = ReactDOM.createRoot(mapContainer);
        root.render(
            <React.StrictMode>
                <DCMapComponent locations={dcLocations} />
            </React.StrictMode>
        );
    }
});