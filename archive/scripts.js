// Initialize the map
const map = L.map('map').setView([-35.0, 149.0], 5);

// Add base layer
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
}).addTo(map);

// Custom icons
const substationIcon = L.divIcon({
    className: 'substation-marker',
    html: '<div style="background-color:#e74c3c; width:24px; height:24px; border-radius:50%; border:2px solid white; box-shadow:0 0 8px rgba(0,0,0,0.5);"></div>',
    iconSize: [24, 24],
    iconAnchor: [12, 12]
});

const wasteFacilityIcon = L.divIcon({
    className: 'waste-marker',
    html: '<div style="background-color:#2ecc71; width:24px; height:24px; border-radius:50%; border:2px solid white; box-shadow:0 0 8px rgba(0,0,0,0.5);"></div>',
    iconSize: [24, 24],
    iconAnchor: [12, 12]
});

// Create layer groups
const substationsLayer = L.layerGroup();
const wasteFacilitiesLayer = L.layerGroup();
const bufferLayer = L.layerGroup();
const measurementLayer = L.layerGroup();

substationsLayer.addTo(map);
wasteFacilitiesLayer.addTo(map);
bufferLayer.addTo(map);
measurementLayer.addTo(map);

// Helper to normalize incoming data (handle GeoJSON or arrays)
function extractFeatures(data) {
    if (!data) return [];
    // GeoJSON FeatureCollection
    if (Array.isArray(data.features)) {
        return data.features.map(f => {
            // merge .properties and geometry.coords for easy access
            return {
                ...f.properties,
                lat: f.geometry && f.geometry.type === "Point" ? f.geometry.coordinates[1] : undefined,
                lng: f.geometry && f.geometry.type === "Point" ? f.geometry.coordinates[0] : undefined
            };
        });
    }
    // Plain array of features/objects
    if (Array.isArray(data)) return data;
    // Error response/object in API
    return [];
}

// Fetch data from APIs and populate the map
Promise.all([
    fetch('api/api.php/substations').then(res => res.json()),
    fetch('api/api.php/waste-facilities').then(res => res.json())
]).then(([substationRaw, wasteFacilityRaw]) => {
    const substations = extractFeatures(substationRaw);
    const wasteFacilities = extractFeatures(wasteFacilityRaw);

    if (!Array.isArray(substations) || !Array.isArray(wasteFacilities)) {
        throw new Error("API returned data in an unexpected format.");
    }

    // Add substations to the map
    substations.forEach(function(station) {
        const lat = parseFloat(station.lat || station.Latitude);
        const lng = parseFloat(station.lng || station.Longitude);
        if (isNaN(lat) || isNaN(lng)) return;
        const marker = L.marker([lat, lng], { icon: substationIcon })
            .addTo(substationsLayer)
            .bindPopup(`
                <div style="min-width: 200px;">
                    <h3 style="margin-bottom: 10px; color: #e74c3c;">${station.name || station['Substation Name'] || station.featuretype || ''}</h3>
                    <p><strong>Status:</strong> ${station.status || station.Status || station.operationalstatus || ''}</p>
                    <p><strong>Voltage:</strong> ${station.voltage || station.Voltage || station.voltagekv || ''} kV</p>
                    <p><strong>State:</strong> ${station.state || station.State || ''}</p>
                </div>
            `);
    });

    // Add waste facilities to the map
    wasteFacilities.forEach(function(facility) {
        const lat = parseFloat(facility.lat || facility.Latitude);
        const lng = parseFloat(facility.lng || facility.Longitude);
        if (isNaN(lat) || isNaN(lng)) return;
        const marker = L.marker([lat, lng], { icon: wasteFacilityIcon })
            .addTo(wasteFacilitiesLayer)
            .bindPopup(`
                <div style="min-width: 200px;">
                    <h3 style="margin-bottom: 10px; color: #2ecc71;">${facility.name || facility['Facility Name'] || facility.facility_name || ''}</h3>
                    <p><strong>Type:</strong> ${facility.type || facility['Facility Type'] || facility.facility_infastructure_type || ''}</p>
                    <p><strong>Address:</strong> ${facility.address || facility.Address || ''}</p>
                    <p><strong>Status:</strong> ${facility.status || facility.Status || facility.operational_status || ''}</p>
                </div>
            `);
    });

    // Layer control functionality
    document.getElementById('substations-layer').addEventListener('change', function(e) {
        if (e.target.checked) {
            map.addLayer(substationsLayer);
        } else {
            map.removeLayer(substationsLayer);
        }
    });

    document.getElementById('waste-facilities-layer').addEventListener('change', function(e) {
        if (e.target.checked) {
            map.addLayer(wasteFacilitiesLayer);
        } else {
            map.removeLayer(wasteFacilitiesLayer);
        }
    });

    // Buffer radius slider
    const radiusSlider = document.getElementById('buffer-radius');
    const radiusValue = document.getElementById('radius-value');

    radiusSlider.addEventListener('input', function() {
        radiusValue.textContent = this.value;
    });

    // Apply buffer functionality
    document.getElementById('apply-buffer').addEventListener('click', function() {
        // Clear existing buffers
        bufferLayer.clearLayers();

        // For demo: buffer around the first substation
        const centerSrc = substations[0];
        if (!centerSrc) return;
        const center = {
            lat: parseFloat(centerSrc.lat || centerSrc.Latitude),
            lng: parseFloat(centerSrc.lng || centerSrc.Longitude),
            name: centerSrc.name || centerSrc['Substation Name'] || ''
        };
        if (isNaN(center.lat) || isNaN(center.lng)) return;
        const radius = parseInt(radiusSlider.value) * 1000;

        // Create buffer circle
        const circle = L.circle([center.lat, center.lng], {
            color: '#4361ee',
            fillColor: '#3a0ca3',
            fillOpacity: 0.2,
            radius: radius
        }).addTo(bufferLayer);

        // Find facilities within the buffer
        let facilitiesInBuffer = 0;
        wasteFacilities.forEach(function(facility) {
            const fLat = parseFloat(facility.lat || facility.Latitude);
            const fLng = parseFloat(facility.lng || facility.Longitude);
            if (isNaN(fLat) || isNaN(fLng)) return;
            const distance = map.distance([center.lat, center.lng], [fLat, fLng]);
            if (distance <= radius) {
                facilitiesInBuffer++;
            }
        });

        document.getElementById('facilities-in-buffer').textContent = facilitiesInBuffer;

        // Update map info
        document.getElementById('map-info').innerHTML = 
            `<i class="fas fa-draw-circle"></i> Applied ${radius/1000}km buffer around ${center.name}. ${facilitiesInBuffer} facilities found within buffer.`;
    });

    // Tool buttons functionality
    const toolButtons = document.querySelectorAll('.tool-btn');
    toolButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            toolButtons.forEach(btn => btn.classList.remove('active'));

            // Add active class to clicked button
            this.classList.add('active');

            // Update map info based on selected tool
            const toolName = this.id.replace('-tool', '');
            let infoText = '';

            switch(toolName) {
                case 'buffer':
                    infoText = '<i class="fas fa-draw-circle"></i> Buffer Tool: Click on a location to create a buffer zone';
                    break;
                case 'measure':
                    infoText = '<i class="fas fa-ruler"></i> Measure Tool: Click on two points to measure distance';
                    break;
                case 'draw':
                    infoText = '<i class="fas fa-pen"></i> Draw Tool: Click to draw a custom area on the map';
                    break;
            }

            document.getElementById('map-info').innerHTML = infoText;
        });
    });

    // Fit map to show all markers
    const allMarkers = [];
    substations.forEach(station => {
        const lat = parseFloat(station.lat || station.Latitude);
        const lng = parseFloat(station.lng || station.Longitude);
        if (!isNaN(lat) && !isNaN(lng)) {
            allMarkers.push([lat, lng]);
        }
    });
    wasteFacilities.forEach(facility => {
        const lat = parseFloat(facility.lat || facility.Latitude);
        const lng = parseFloat(facility.lng || facility.Longitude);
        if (!isNaN(lat) && !isNaN(lng)) {
            allMarkers.push([lat, lng]);
        }
    });

    if (allMarkers.length) {
        const bounds = L.latLngBounds(allMarkers);
        map.fitBounds(bounds, {padding: [50, 50]});
    }
}).catch(err => {
    console.error('Error loading map data:', err);
    document.getElementById('map-info').innerHTML = '<span style="color: red;">Failed to load map data.</span>';
});