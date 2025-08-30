// mapMarkers.js
// Handles adding and clearing markers on the map for substations and waste facilities

// Expects global: map, substationsLayer, wasteFacilitiesLayer, substationIcon, wasteFacilityIcon

export function updateSubstationMarkers(substations) {
    substationsLayer.clearLayers();
    substations.forEach(station => {
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
}

export function updateWasteFacilityMarkers(facilities) {
    wasteFacilitiesLayer.clearLayers();
    facilities.forEach(facility => {
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
}
