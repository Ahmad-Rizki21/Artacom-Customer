// resources/js/dcMap.jsx
import React from 'react';
import ReactDOM from 'react-dom/client';
import DCMapComponent from './components/DCMapComponent';

// Mount the React component when the DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    const mapContainer = document.getElementById('dc-map');
    if (mapContainer) {
        const root = ReactDOM.createRoot(mapContainer);
        root.render(
            <React.StrictMode>
                <DCMapComponent locations={window.dcLocations || []} />
            </React.StrictMode>
        );
    }
});