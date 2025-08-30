<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Geospatial Data Analysis Map</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>Geospatial Data Analysis Map</h1>
        <p class="subtitle">Visualizing Substations and Waste Facilities for Decision Making</p>
    </header>
    
    <div class="container">
        <div class="sidebar">
            <div class="control-panel">
                <h2>Map Layers</h2>
                <div class="layer-control">
                    <input type="checkbox" id="substations-layer" checked>
                    <label for="substations-layer">Substations (Dataset 1)</label>
                </div>
                <div class="layer-control">
                    <input type="checkbox" id="waste-facilities-layer" checked>
                    <label for="waste-facilities-layer">Waste Facilities (Dataset 2)</label>
                </div>
            </div>
            
            <div class="legend">
                <h2>Legend</h2>
                <div class="legend-item">
                    <div class="legend-icon substation-icon"></div>
                    <span>Substation</span>
                </div>
                <div class="legend-item">
                    <div class="legend-icon waste-icon"></div>
                    <span>Waste Facility</span>
                </div>
            </div>
            
            <div class="stats">
                <h2>Statistics</h2>
                <div id="substation-count">Substations: 3</div>
                <div id="waste-facility-count">Waste Facilities: 2</div>
                <div id="total-features">Total Features: 5</div>
            </div>
        </div>
        
        <div id="map"></div>
    </div>
    
    <footer>
        <p>Geospatial Data Analysis Tool | Created for decision support</p>
    </footer>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="scripts.js"></script>
</body>
</html>