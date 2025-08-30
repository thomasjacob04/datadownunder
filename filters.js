// filters.js
// Handles filter logic, debug logging, and filter event listeners
// Expects global: substations, wasteFacilities
// Uses: updateSubstationMarkers, updateWasteFacilityMarkers from mapMarkers.js

import { updateSubstationMarkers, updateWasteFacilityMarkers } from './mapMarkers.js';

export function debugLogFilterState(statusFilter, voltageFilter, typeFilter) {
    console.log(
        `Status filter: ${statusFilter.value}, Voltage filter: ${voltageFilter.value}, Type filter: ${typeFilter.value}`
    );
}

export function applyFiltersAndUpdate({
    substations,
    wasteFacilities,
    statusFilter,
    voltageFilter,
    typeFilter
}) {
    debugLogFilterState(statusFilter, voltageFilter, typeFilter);

    // Substation filtering
    let filteredSubs = substations.slice();
    if (statusFilter.value !== "all") {
        filteredSubs = filteredSubs.filter(station => {
            const statusValue = (
                station.status ||
                station.Status ||
                station.operationalstatus || ''
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
    if (voltageFilter.value !== "all") {
        filteredSubs = filteredSubs.filter(station => {
            let v = (
                station.voltage || station.Voltage || station.voltagekv || station['Voltage (kV)'] || ''
            );
            v = parseFloat(v);
            if (voltageFilter.value === "low") return v < 100;
            if (voltageFilter.value === "high") return v >= 100;
            return true;
        });
    }

    // Waste facility filtering
    let filteredWaste = wasteFacilities.slice();
    if (typeFilter.value !== "all") {
        filteredWaste = filteredWaste.filter(facility => {
            const typeValue = (
                facility.type || facility['Facility Type'] || facility.facility_infastructure_type || ''
            ).toLowerCase();
            return typeValue.indexOf(typeFilter.value) !== -1;
        });
    }
    if (statusFilter.value !== "all") {
        filteredWaste = filteredWaste.filter(facility => {
            const statusValue = (
                facility.status ||
                facility.Status ||
                facility.operational_status || ''
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

    // Debug outputs
    console.log("Filtered substations:", filteredSubs.length, filteredSubs);
    console.log("Filtered waste facilities:", filteredWaste.length, filteredWaste);

    // Update Map Layers
    updateSubstationMarkers(filteredSubs);
    updateWasteFacilityMarkers(filteredWaste);

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
    if (allMarkers.length) {
        const bounds = L.latLngBounds(allMarkers);
        map.fitBounds(bounds, {padding: [50, 50]});
    }
}

export function attachFilterListeners({substations, wasteFacilities, statusFilter, voltageFilter, typeFilter}) {
    function rerun() {
        applyFiltersAndUpdate({
            substations,
            wasteFacilities,
            statusFilter,
            voltageFilter,
            typeFilter
        });
    }
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            console.log("Status filter changed:", this.value);
            rerun();
        });
    }
    if (voltageFilter) {
        voltageFilter.addEventListener('change', function() {
            console.log("Voltage filter changed:", this.value);
            rerun();
        });
    }
    if (typeFilter) {
        typeFilter.addEventListener('change', function() {
            console.log("Type filter changed:", this.value);
            rerun();
        });
    }
    // Initial run
    rerun();
}
