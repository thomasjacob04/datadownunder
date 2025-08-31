<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Geospatial Analysis Map with Enhanced Tools</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            display: flex;
            flex-direction: column;
            height: 100vh;
            background-color: #1a1a2e;
            color: #f0f0f0;
        }
        
        header {
            background: linear-gradient(135deg, #162947, #1a1a2e);
            color: white;
            padding: 1rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }
        
        h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(45deg, #4cc9f0, #4361ee);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
        }
        
        .subtitle {
            font-size: 1rem;
            opacity: 0.9;
            max-width: 800px;
            color: #b8c1ec;
        }
        
        .tool-selection {
            display: flex;
            gap: 10px;
        }
        
        .tool-btn {
            background: linear-gradient(135deg, #4361ee, #3a0ca3);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }
        
        .tool-btn:hover {
            background: linear-gradient(135deg, #3a0ca3, #4361ee);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        
        .tool-btn.active {
            background: linear-gradient(135deg, #f72585, #b5179e);
            box-shadow: 0 0 0 3px rgba(247, 37, 133, 0.3);
        }
        
        .container {
            display: flex;
            flex: 1;
            overflow: hidden;
        }
        
        #map {
            flex: 1;
            height: 100%;
            z-index: 1;
        }
        
        .sidebar {
            width: 320px;
            background: linear-gradient(180deg, #162947, #1e1e2e);
            padding: 1.2rem;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .control-panel {
            background: rgba(255, 255, 255, 0.05);
            padding: 1.2rem;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        h2 {
            font-size: 1.3rem;
            margin-bottom: 1rem;
            color: #4cc9f0;
            border-bottom: 2px solid #4361ee;
            padding-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .layer-control {
            display: flex;
            align-items: center;
            margin-bottom: 0.8rem;
            padding: 8px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .layer-control:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .layer-control input {
            margin-right: 10px;
            accent-color: #4361ee;
        }
        
        .filter-section {
            margin-top: 15px;
        }
        
        .filter-group {
            margin-bottom: 15px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #b8c1ec;
        }
        
        .filter-group select {
            width: 100%;
            padding: 8px;
            border-radius: 5px;
            border: 1px solid #4361ee;
            background: rgba(26, 26, 46, 0.8);
            color: white;
        }
        
        .buffer-control {
            margin-top: 15px;
        }
        
        .buffer-control label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #b8c1ec;
        }
        
        .buffer-control input {
            width: 100%;
            margin-bottom: 10px;
        }
        
        .buffer-control button {
            width: 100%;
            padding: 8px;
            background: linear-gradient(135deg, #4361ee, #3a0ca3);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .buffer-control button:hover {
            background: linear-gradient(135deg, #3a0ca3, #4361ee);
        }
        
        .legend {
            background: rgba(255, 255, 255, 0.05);
            padding: 1.2rem;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.8rem;
        }
        
        .legend-icon {
            width: 20px;
            height: 20px;
            margin-right: 10px;
            border-radius: 50%;
            border: 2px solid white;
        }
        
        .substation-icon {
            background-color: #e74c3c;
        }
        
        .waste-icon {
            background-color: #2ecc71;
        }
        
        .buffer-icon {
            background-color: rgba(67, 97, 238, 0.3);
        }
        
        .stats {
            background: rgba(255, 255, 255, 0.05);
            padding: 1.2rem;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .stats div {
            margin-bottom: 0.8rem;
            padding: 0.8rem;
            background: rgba(26, 26, 46, 0.5);
            border-radius: 5px;
            border-left: 4px solid #4361ee;
        }
        
        .stats h3 {
            color: #4cc9f0;
            margin-bottom: 5px;
        }
        
        footer {
            text-align: center;
            padding: 1rem;
            background: #162947;
            color: #b8c1ec;
            font-size: 0.9rem;
            box-shadow: 0 -4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .map-info {
            position: absolute;
            bottom: 20px;
            left: 20px;
            background: rgba(26, 26, 46, 0.8);
            padding: 10px 15px;
            border-radius: 8px;
            z-index: 1000;
            color: white;
            font-size: 14px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            max-width: 300px;
        }
        
        @media (max-width: 1024px) {
            .container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                max-height: 40%;
                flex-direction: row;
                flex-wrap: wrap;
                overflow-x: auto;
                gap: 15px;
            }
            
            .control-panel, .legend, .stats {
                flex: 1;
                min-width: 300px;
            }
            
            .header-content {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .tool-selection {
                width: 100%;
                justify-content: center;
            }
        }
        
        /* Range slider styling */
        input[type="range"] {
            -webkit-appearance: none;
            width: 100%;
            height: 8px;
            border-radius: 5px;
            background: rgba(255, 255, 255, 0.1);
            outline: none;
        }
        
        input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #4361ee;
            cursor: pointer;
            box-shadow: 0 0 0 2px white;
        }
        
        /* Toggle switch */
        .toggle {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
            margin-right: 10px;
        }
        
        .toggle input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background: linear-gradient(135deg, #4361ee, #3a0ca3);
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <div>
                <h1>Geospatial Analysis Map</h1>
                <p class="subtitle">Enhanced analysis tools with buffer zones, filtering, and measurement</p>
            </div>
            <div class="tool-selection">
                <button class="tool-btn" id="buffer-tool">
                    <i class="fas fa-draw-circle"></i> Buffer Tool
                </button>
                <button class="tool-btn" id="measure-tool">
                    <i class="fas fa-ruler"></i> Measure Distance
                </button>
                <button class="tool-btn" id="draw-tool">
                    <i class="fas fa-pen"></i> Draw Area
                </button>
            </div>
        </div>
    </header>
    
    <div class="container">
        <div class="sidebar">
            <div class="control-panel">
                <h2><i class="fas fa-layer-group"></i> Map Layers</h2>
                <!-- <div class="layer-control">
                    <label class="toggle">
                        <input type="checkbox" id="substations-layer" checked>
                        <span class="slider"></span>
                    </label>
                    <label for="substations-layer">Substations (Dataset 1)</label>
                </div> -->
                <div class="layer-control">
                    <label class="toggle">
                        <input type="checkbox" id="waste-facilities-layer" checked>
                        <span class="slider"></span>
                    </label>
                    <label for="waste-facilities-layer">Waste Facilities (Dataset 2)</label>
                </div>
                <div class="layer-control">
                    <label class="toggle">
                        <input type="checkbox" id="major-power-stations-layer" checked>
                        <span class="slider"></span>
                    </label>
                    <label for="major-power-stations-layer">Major power stations</label>
                </div>
                
                <div class="filter-section">
                    <h2><i class="fas fa-filter"></i> Filters</h2>
                    
                    <div class="filter-group">
                        <label for="status-filter">Operational Status</label>
                        <select id="status-filter">
                            <option value="all">All Statuses</option>
                            <option value="operational">Operational</option>
                            <option value="non-operational">Non-Operational</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="voltage-filter">Voltage (kV)</label>
                        <select id="voltage-filter">
                            <option value="all">All Voltages</option>
                            <option value="low">Low Voltage (< 100kV)</option>
                            <option value="high">High Voltage (≥ 100kV)</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="type-filter">Facility Type</label>
                        <select id="type-filter">
                            <option value="all">All Types</option>
                            <option value="recycling">Recycling</option>
                            <option value="quarry">Quarry</option>
                            <option value="wastewater">Wastewater</option>
                        </select>
                    </div>
                </div>
                
                <div class="buffer-control">
                    <h2><i class="fas fa-draw-circle"></i> Buffer Settings</h2>
                    <label for="buffer-radius">Buffer Radius (km): <span id="radius-value">50</span></label>
                    <input type="range" id="buffer-radius" min="1" max="100" value="50">
                    <button id="apply-buffer">Apply Buffer to Selection</button>
                </div>
            </div>
            
            <div class="legend">
                <h2><i class="fas fa-map-signs"></i> Legend</h2>
                <div class="legend-item">
                    <div class="legend-icon substation-icon"></div>
                    <span>Substation</span>
                </div>
                <div class="legend-item">
                    <div class="legend-icon waste-icon"></div>
                    <span>Waste Facility</span>
                </div>
                <div class="legend-item">
                    <div class="legend-icon buffer-icon"></div>
                    <span>Buffer Zone</span>
                </div>
            </div>
            
            <div class="stats">
                <h2><i class="fas fa-chart-bar"></i> Statistics</h2>
                <div>
                    <h3>Power Stations</h3>
                    <p>Total: <span id="substation-count">3</span></p>
                    <p>Operational: <span id="operational-substations">3</span></p>
                </div>
                <div>
                    <h3>Waste Facilities</h3>
                    <p>Total: <span id="waste-facility-count">2</span></p>
                    <p>Operational: <span id="operational-waste-facilities">2</span></p>
                </div>
                <div>
                    <h3>Buffer Analysis</h3>
                    <p>Facilities in buffer: <span id="facilities-in-buffer">0</span></p>
                </div>
            </div>
        </div>
        
        <div id="map"></div>
        <div class="map-info" id="map-info">
            <i class="fas fa-info-circle"></i> Select a tool from the header to begin analysis
        </div>
    </div>
    
    <footer>
        <p>Geospatial Data Analysis Tool | Enhanced Analysis Tools with Buffer Zones, Filtering, and Measurement</p>
    </footer>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.heat/dist/leaflet-heat.js"></script>
    <script src="scripts_four.js"></script>
    <script src="heatmix.js"></script>
    <script src="heatmap_loader.js"></script>
</body>
</html>