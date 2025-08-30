// heatmap_loader.js
// Assumes: heatmix.js loaded, global 'map' exists

const DATASET_CONFIG = [
  { url: 'api/api.php/substations',   color: 'red',   selector: f => ({ lat: f.lat || f.Latitude, lng: f.lng || f.Longitude }) },
  { url: 'api/api.php/waste-facilities', color: 'green', selector: f => ({ lat: f.lat || f.Latitude, lng: f.lng || f.Longitude }) },
  { url: 'api/api.php/power-stations',   color: 'blue',  selector: f => ({ lat: f.y_coordinate || f.lat || f.Latitude, lng: f.x_coordinate || f.lng || f.Longitude }) }
];
const colorWeights = { red: 1, green: 1, blue: 1 };

// How close (meters) must the points be to count as a mixed cluster
const proximityDistanceMeters = 1000;

// Simple Haversine formula
function haversine(lat1, lng1, lat2, lng2) {
    const R = 6371000;
    const toRad = x => x * Math.PI / 180;
    const dLat = toRad(lat2 - lat1);
    const dLng = toRad(lng2 - lng1);
    const a =
        Math.sin(dLat / 2) ** 2 +
        Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng / 2) ** 2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

function normalizeFeatures(data) {
    if (Array.isArray(data)) return data;
    if (data && Array.isArray(data.features)) {
        return data.features.map(f =>
            Object.assign({}, f.properties || {}, {
                lat: f.geometry && f.geometry.type === "Point" ? f.geometry.coordinates[1] : undefined,
                lng: f.geometry && f.geometry.type === "Point" ? f.geometry.coordinates[0] : undefined
            })
        );
    }
    return [];
}

Promise.all(
  DATASET_CONFIG.map(cfg =>
    fetch(cfg.url)
      .then(r => r.json())
      .then(normalizeFeatures)
      .then(features =>
        features
          .map(cfg.selector)
          .filter(f => f.lat && f.lng)
          .map(f => ({ lat: parseFloat(f.lat), lng: parseFloat(f.lng), color: cfg.color }))
      )
  )
).then(datasetPointsArr => {
    // Combine all points
    const allPoints = datasetPointsArr.flat();

    // Now, build candidate heat points ONLY where all colors are within proximityDistanceMeters.
    // We'll use a simple iterative scan. For large N, use a spatial index (not shown here).

    const resultHeatPoints = [];
    const requiredColors = Object.keys(colorWeights);

    for (let i = 0; i < allPoints.length; ++i) {
        const p = allPoints[i];

        // For each other color, see if a point in the neighborhood exists
        const colorsFound = new Set([p.color]);
        for (let j = 0; j < allPoints.length; ++j) {
            if (j === i) continue;
            const q = allPoints[j];
            if (
                q.color !== p.color &&
                !colorsFound.has(q.color) &&
                haversine(p.lat, p.lng, q.lat, q.lng) <= proximityDistanceMeters
            ) {
                colorsFound.add(q.color);
                if (colorsFound.size === requiredColors.length) break;
            }
        }
        // Only if all required colors found
        if (colorsFound.size === requiredColors.length) {
            // Place one heat point for each color here (or just once)
            // Weight can be adapted if desired:
            resultHeatPoints.push({
                lat: p.lat,
                lng: p.lng,
                color: p.color
            });
        }
    }

    // FINAL: Only clustered, blended points sent to the heatmap
    addMixedHeatmapLayer(resultHeatPoints, colorWeights);

});