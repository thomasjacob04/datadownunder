// heatmix.js
// Requires: Leaflet, Leaflet.heat, a global 'map'
// Provides: addMixedHeatmapLayer(points, colorWeights, options)

// Assign your colours and weights, e.g.:
// const colorWeights = { red: 1, green: 1, blue: 1 }
// Example array (gathered from your app's markers)
// let points = [
//     { lat: -35.12, lng: 149.1, color: 'red' },
//     { lat: -35.13, lng: 149.1, color: 'green' },
//     { lat: -35.14, lng: 149.12, color: 'blue' }
// ];
// let weights = { red: 2, green: 1, blue: 3 };
// let heat = addMixedHeatmapLayer(points, weights);
// To remove: heat.remove();
// Accepts: [{lat, lng, color} ...], colorWeights {red: 3, green: 2, ...}, options for L.heatLayer
function addMixedHeatmapLayer(points, colorWeights, options = {}) {
    // Group all points by color
    const pointsByColor = {};
    Object.keys(colorWeights).forEach(c => pointsByColor[c] = []);
    points.forEach(pt => {
        if (pointsByColor[pt.color]) {
            pointsByColor[pt.color].push([pt.lat, pt.lng]);
        }
    });

    // For each color, create a heatmap, using its weight and a distinct gradient
    const colorHeatLayers = {};
    for (const color in pointsByColor) {
        const weightedLatLng = pointsByColor[color].map(([lat, lng]) =>
            [lat, lng, colorWeights[color]]
        );
        colorHeatLayers[color] = L.heatLayer(
            weightedLatLng,
            Object.assign({
                radius: 40,
                blur: 25,
                minOpacity: 0.18,
                gradient: (() => {
                    // Set strong distinct gradient for each colour
                    let g = {};
                    g[0] = color;
                    g[0.8] = color;
                    g[1] = color;
                    return g;
                })()
            }, options)
        ).addTo(map);
    }

    // Return a composite heat group for further control
    return {
        layers: colorHeatLayers,
        remove: () => Object.values(colorHeatLayers).forEach(layer => map.removeLayer(layer))
    };
}

// Example usage (after all markers have been added or their data loaded):
//
// let allPoints = [
//   {lat: ..., lng: ..., color: 'red'},
//   {lat: ..., lng: ..., color: 'blue'},
//   ...
// ];
// let weights = {red: 2, blue: 1, green: 1};
// let heat = addMixedHeatmapLayer(allPoints, weights);
// heat.remove(); // To remove the heat blobs
