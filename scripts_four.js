// Initialize the map
const map = L.map('map').setView([-35.0, 149.0], 5);

// Add base layer
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
}).addTo(map);

// Custom icons
const substationIcon = L.divIcon({
    className: 'substation-marker',
    html: '<div style="background-color:#2980b9; width:4px; height:4px; border-radius:50%;"></div>',
    iconSize: [24, 24],
    iconAnchor: [12, 12]
});

const wasteFacilityIcon = L.divIcon({
    className: 'waste-marker',
    html: '<div style="background-color:#2ecc71; width:6px; height:6px; border-radius:20%;"></div>',
    iconSize: [24, 24],
    iconAnchor: [12, 12]
});

const powerStationIcon = L.divIcon({
    className: 'power-station-marker',
    html: '<div style="background-color:#2980b9; width:6px; height:6px; border-radius:50%; box-shadow: 0 0 2px #fff;"></div>',
    iconSize: [26, 26],
    iconAnchor: [13, 13]
});

// Create layer groups
const substationsLayer = L.layerGroup();
const wasteFacilitiesLayer = L.layerGroup();
const powerStationsLayer = L.layerGroup();
const bufferLayer = L.layerGroup();
const measurementLayer = L.layerGroup();

substationsLayer.addTo(map);
wasteFacilitiesLayer.addTo(map);
powerStationsLayer.addTo(map);
bufferLayer.addTo(map);
measurementLayer.addTo(map);

// Normalize features from GeoJSON or array
function normalizeFeatures(data) {
    if (Array.isArray(data)) return data;
    if (data && Array.isArray(data.features)) {
        // GeoJSON FeatureCollection: convert to flat array with geometry/props merged
        return data.features.map(f => ({
            ...(f.properties || {}),
            lat: f.geometry && f.geometry.type === "Point" ? f.geometry.coordinates[1] : undefined,
            lng: f.geometry && f.geometry.type === "Point" ? f.geometry.coordinates[0] : undefined
        }));
    }
    // Defensive: not an array nor GeoJSON type
    return [];
}

// ---------- ADVANCED FILTERING & DEBUG ENABLED DATA LOADING ----------

let substations = [];
let wasteFacilities = [];
let powerStations = [];

// Fetch data from APIs and populate the map
Promise.all([
    fetch('api/api.php/substations_stop').then(res => res.json()),
    fetch('api/api.php/waste-facilities').then(res => res.json()),
    fetch('api/api.php/power-stations').then(res => res.json())
]).then(([substationRaw, wasteFacilityRaw, powerStationRaw]) => {

    substations = normalizeFeatures(substationRaw);
    wasteFacilities = normalizeFeatures(wasteFacilityRaw);
    powerStations = normalizeFeatures(powerStationRaw);

    // ---- FILTER + DEBUG ----
    // Find filter select elements
    const statusFilter = document.getElementById('status-filter');
    const voltageFilter = document.getElementById('voltage-filter');
    const typeFilter = document.getElementById('type-filter');

    function debugLogFilterState() {
        console.log(
            `Status filter: ${statusFilter.value}, Voltage filter: ${voltageFilter.value}, Type filter: ${typeFilter.value}`
        );
    }

    // Generic filter: updates markers and counts, logs results
    function applyFiltersAndUpdate() {
        debugLogFilterState();

        // --------- Substation filtering ----------
        let filteredSubs = substations.slice();

        // Status filter (for substations)
        if (statusFilter.value !== "all") {
            filteredSubs = filteredSubs.filter(station => {
                const statusValue = (
                    station.status ||
                    station.Status ||
                    station.operationalstatus ||
                    ''
                ).toLowerCase();
                if (statusFilter.value === "operational") {
                    return statusValue === "operational";
                }
                if (statusFilter.value === "non-operational") {
                    return statusValue && statusValue !== "operational";
                }
                return true; // fallback
            });
        }

        // Voltage filter (only for substations)
        if (voltageFilter.value !== "all") {
            filteredSubs = filteredSubs.filter(station => {
                let v = (
                    station.voltage ||
                    station.Voltage ||
                    station.voltagekv ||
                    station['Voltage (kV)'] ||
                    ''
                );
                v = parseFloat(v);
                if (voltageFilter.value === "low") return v < 100;
                if (voltageFilter.value === "high") return v >= 100;
                return true;
            });
        }

        // --------- Waste facility filtering ----------
        let filteredWaste = wasteFacilities.slice();

        // Type filter (for waste facilities only)
        if (typeFilter.value !== "all") {
            filteredWaste = filteredWaste.filter(facility => {
                const typeValue = (
                    facility.type ||
                    facility['Facility Type'] ||
                    facility.facility_infastructure_type ||
                    ''
                ).toLowerCase();
                return typeValue.indexOf(typeFilter.value) !== -1;
            });
        }

        
        // Status filter (operational only)
        if (statusFilter.value !== "all") {
            filteredWaste = filteredWaste.filter(facility => {
                const statusValue = (
                    facility.status ||
                    facility.Status ||
                    facility.operational_status ||
                    ''
                ).toLowerCase();
                if (statusFilter.value === "operational") {
                    return statusValue === "operational";
                }
                if (statusFilter.value === "non-operational") {
                    return statusValue && statusValue !== "operational";
                }
                return true;
            });
        }

        // ------- Power Station display (no advanced filter for now) -------
        let filteredPowerStations = powerStations.slice();

        // --- DEBUG OUTPUTS ---
        console.log("Filtered substations:", filteredSubs.length, filteredSubs);
        console.log("Filtered waste facilities:", filteredWaste.length, filteredWaste);
        console.log("Power stations:", filteredPowerStations.length, filteredPowerStations);

        // --- Update Map Layers ---
        substationsLayer.clearLayers();
        wasteFacilitiesLayer.clearLayers();
        powerStationsLayer.clearLayers();

        filteredSubs.forEach(station => {
            const marker = L.marker(
                [parseFloat(station.lat || station.Latitude), parseFloat(station.lng || station.Longitude)],
                {icon: substationIcon}
            ).addTo(substationsLayer)
            .bindPopup(`
            <div style="min-width: 200px;">
            <h3 style="margin-bottom: 10px; color: #e74c3c;">${station.name || station['Substation Name']}</h3>
            <p><strong>Status:</strong> ${station.status || station.Status || station.operationalstatus || ''}</p>
            <p><strong>Voltage:</strong> ${station.voltage || station.Voltage || station.voltagekv || ''} kV</p>
            <p><strong>State:</strong> ${station.state || station.State || ''}</p>
            </div>
            `);
        });

        filteredWaste.forEach(facility => {
            const marker = L.marker(
                [parseFloat(facility.lat || facility.Latitude), parseFloat(facility.lng || facility.Longitude)],
                {icon: wasteFacilityIcon}
            ).addTo(wasteFacilitiesLayer)
            .bindPopup(`
            <div style="min-width: 200px;">
            <h3 style="margin-bottom: 10px; color: #2ecc71;">${facility.name || facility['Facility Name']}</h3>
            <p><strong>Type:</strong> ${facility.type || facility['Facility Type'] || facility.facility_infastructure_type || ''}</p>
            <p><strong>Address:</strong> ${facility.address || facility.Address || ''}</p>
            <p><strong>Status:</strong> ${facility.status || facility.Status || facility.operational_status || ''}</p>
            </div>
            `);
        });

        filteredPowerStations.forEach(station => {
            const marker = L.marker(
                [parseFloat(station.y_coordinate || station.lat || station.Latitude), parseFloat(station.x_coordinate || station.lng || station.Longitude)],
                {icon: powerStationIcon}
            ).addTo(powerStationsLayer)
            .bindPopup(`
            <div style="min-width: 220px;">
            <h3 style="margin-bottom: 10px; color: #2980b9;">${station.name}</h3>
            <p><strong>Status:</strong> ${station.operationalstatus || ''}</p>
            <p><strong>Type:</strong> ${station.generationtype || ''}</p>
            <p><strong>Fuel:</strong> ${station.primaryfueltype || ''}</p>
            <p><strong>MW:</strong> ${station.generationmw || ''}</p>
            <p><strong>State:</strong> ${station.state || ''}</p>
            </div>
            `);
        });

        document.getElementById('substation-count').textContent = filteredSubs.length;
        document.getElementById('operational-substations').textContent =
            filteredSubs.filter(station =>
                (station.status || station.Status || station.operationalstatus || '').toLowerCase() === "operational"
            ).length;
        document.getElementById('waste-facility-count').textContent = filteredWaste.length;
        document.getElementById('operational-waste-facilities').textContent =
            filteredWaste.filter(facility =>
                (facility.status || facility.Status || facility.operational_status || '').toLowerCase() === "operational"
            ).length;

        // Optionally, fit bounds
        const allMarkers = [];
        filteredSubs.forEach(station => {
            allMarkers.push([
                parseFloat(station.lat || station.Latitude),
                parseFloat(station.lng || station.Longitude)
            ]);
        });
        filteredWaste.forEach(facility => {
            allMarkers.push([
                parseFloat(facility.lat || facility.Latitude),
                parseFloat(facility.lng || facility.Longitude)
            ]);
        });
        filteredPowerStations.forEach(station => {
            allMarkers.push([
                parseFloat(station.y_coordinate || station.lat || station.Latitude),
                parseFloat(station.x_coordinate || station.lng || station.Longitude)
            ]);
        });
        if (allMarkers.length) {
            const bounds = L.latLngBounds(allMarkers);
            map.fitBounds(bounds, {padding: [50, 50]});
        }
    }

    // Attach filter listeners WITH debug logging
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            console.log("Status filter changed:", this.value);
            applyFiltersAndUpdate();
        });
    }
    if (voltageFilter) {
        voltageFilter.addEventListener('change', function() {
            console.log("Voltage filter changed:", this.value);
            applyFiltersAndUpdate();
        });
    }
    if (typeFilter) {
        typeFilter.addEventListener('change', function() {
            console.log("Type filter changed:", this.value);
            applyFiltersAndUpdate();
        });
    }

    // Layer controls for all datasets
    const powerStationsLayerCheckbox = document.getElementById('major-power-stations-layer');
    if(powerStationsLayerCheckbox) {
        powerStationsLayerCheckbox.addEventListener('change', function(e) {
            if (e.target.checked) {
                map.addLayer(powerStationsLayer);
            } else {
                map.removeLayer(powerStationsLayer);
            }
        });
    }

    const substationsLayerCheckbox = document.getElementById('substations-layer');
    if(substationsLayerCheckbox) {
        substationsLayerCheckbox.addEventListener('change', function(e) {
            if (e.target.checked) {
                map.addLayer(substationsLayer);
            } else {
                map.removeLayer(substationsLayer);
            }
        });
    }

    const wasteFacilitiesLayerCheckbox = document.getElementById('waste-facilities-layer');
    if(wasteFacilitiesLayerCheckbox) {
        wasteFacilitiesLayerCheckbox.addEventListener('change', function(e) {
            if (e.target.checked) {
                map.addLayer(wasteFacilitiesLayer);
            } else {
                map.removeLayer(wasteFacilitiesLayer);
            }
        });
    }

    // Initial run!
    applyFiltersAndUpdate();
    

}).catch(err => {
    console.error('Error loading map data:', err);
    document.getElementById('map-info').innerHTML = '<span style="color: red;">Failed to load map data.</span>';
});