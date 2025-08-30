<?php
/**
 * API Endpoints for CSV Data Access
 * Provides RESTful access to dataset1.csv and dataset2.csv
 */

// Enable CORS for cross-domain requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Set content type to JSON
header('Content-Type: application/json');

// Define CSV file paths
define('DATASET1', '../data/Waste_Management_Facilities.csv');
define('DATASET2', '../data/DataDownUnder/datasets/Transmission_Substations.csv');
define('DATASET3', '../data/DataDownUnder/datasets/Major_Power_Stations.csv');
define('DATASET4', '../data/DataDownUnder/datasets/telecommunications_new_developments.csv');
// Get the requested endpoint
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path_parts = explode('/', $path);
$endpoint = end($path_parts);

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Route the request
switch ($endpoint) {
    case 'substations':
        getSubstations();
        break;
    case 'waste-facilities':
        getWasteFacilities();
        break;
    case 'power-stations':
        getPowerStations();
        break;
        case 'telecom-developments':
        getTelecomDevelopments();
        break;
    case 'all-data':
        getAllData();
        break;
    case 'nearby':
        getNearbyFacilities();
        break;
    default:
        apiHome();
        break;
}

/**
 * Display API information
 */
function apiHome() {
    $response = [
        'message' => 'Geospatial Data API',
        'endpoints' => [
            '/substations' => 'Get all substation data from dataset1.csv',
            '/waste-facilities' => 'Get all waste facility data from dataset2.csv',
            '/power-stations' => 'Get all major power stations data from Major_Power_Stations.csv',
            '/all-data' => 'Get combined data from both datasets',
            '/nearby?lat={latitude}&lng={longitude}&radius={km}' => 'Find facilities near a location'
        ],
        'note' => 'All data returned in GeoJSON format for mapping'
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
}

/**
 * Get substation data from dataset1.csv
 */
function getSubstations() {
    if (!file_exists(DATASET1)) {
        http_response_code(404);
        echo json_encode(['error' => 'Dataset1 not found']);
        return;
    }
    
    $data = csvToGeoJson(DATASET1, 'substation');
    echo json_encode($data, JSON_PRETTY_PRINT);
}

/**
 * Get waste facility data from dataset2.csv
 */
function getWasteFacilities() {
    if (!file_exists(DATASET2)) {
        http_response_code(404);
        echo json_encode(['error' => 'Dataset2 not found']);
        return;
    }
    
    $data = csvToGeoJson(DATASET2, 'waste_facility');
    echo json_encode($data, JSON_PRETTY_PRINT);
}

/**
 * Get major power station data from DATASET3
 */
function getPowerStations() {
    if (!file_exists(DATASET3)) {
        http_response_code(404);
        echo json_encode(['error' => 'Major Power Stations not found']);
        return;
    }

    $data = csvToGeoJson(DATASET3, 'power_station');
    echo json_encode($data, JSON_PRETTY_PRINT);
}

/**
 * Get combined data from both datasets
 */
function getAllData() {
    $substations = file_exists(DATASET1) ? csvToGeoJson(DATASET1, 'substation') : [];
    $wasteFacilities = file_exists(DATASET2) ? csvToGeoJson(DATASET2, 'waste_facility') : [];
    
    $combined = [
        'type' => 'FeatureCollection',
        'features' => array_merge(
            $substations['features'] ?? [],
            $wasteFacilities['features'] ?? []
        )
    ];
    
    echo json_encode($combined, JSON_PRETTY_PRINT);
}

/**
 * Find facilities near a specified location
 */
function getNearbyFacilities() {
    if (!isset($_GET['lat']) || !isset($_GET['lng'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Latitude and longitude parameters required']);
        return;
    }
    
    $lat = floatval($_GET['lat']);
    $lng = floatval($_GET['lng']);
    $radius = isset($_GET['radius']) ? floatval($_GET['radius']) : 10; // Default 10km radius
    
    // Get all data
    $substations = file_exists(DATASET1) ? csvToGeoJson(DATASET1, 'substation') : ['features' => []];
    $wasteFacilities = file_exists(DATASET2) ? csvToGeoJson(DATASET2, 'waste_facility') : ['features' => []];
    
    $allFeatures = array_merge($substations['features'], $wasteFacilities['features']);
    $nearbyFeatures = [];
    
    foreach ($allFeatures as $feature) {
        $featureLat = $feature['geometry']['coordinates'][1];
        $featureLng = $feature['geometry']['coordinates'][0];
        
        $distance = calculateDistance($lat, $lng, $featureLat, $featureLng);
        
        if ($distance <= $radius) {
            $feature['properties']['distance_km'] = round($distance, 2);
            $nearbyFeatures[] = $feature;
        }
    }
    
    // Sort by distance
    usort($nearbyFeatures, function($a, $b) {
        return $a['properties']['distance_km'] <=> $b['properties']['distance_km'];
    });
    
    $result = [
        'type' => 'FeatureCollection',
        'features' => $nearbyFeatures
    ];
    
    echo json_encode($result, JSON_PRETTY_PRINT);
}

/**
 * Convert CSV data to GeoJSON format
 */

function csvToGeoJson($csvFile, $type) {
    $handle = fopen($csvFile, 'r');
    if (!$handle) {
        return ['error' => 'Failed to open CSV file'];
    }

    $headers = fgetcsv($handle);
    $features = [];

    while (($row = fgetcsv($handle)) !== FALSE) {
        // Optionally, skip rows with too few columns
        if ($type === 'substation' && count($row) < 17) continue;
        if ($type === 'power_station' && count($row) < 21) continue;
        if ($type !== 'substation' && $type !== 'power_station' && count($row) < 12) continue;

        $properties = [];

        // Handle different CSV structures
        if ($type === 'substation') {
            $lng = isset($row[0]) ? $row[0] : null;
            $lat = isset($row[1]) ? $row[1] : null;

            $properties = [
                'objectid'          => isset($row[2]) ? $row[2] : null,
                'featuretype'       => isset($row[3]) ? $row[3] : null,
                'class'             => isset($row[4]) ? $row[4] : null,
                'name'              => isset($row[5]) ? $row[5] : null,
                'operationalstatus' => isset($row[6]) ? $row[6] : null,
                'state'             => isset($row[7]) ? $row[7] : null,
                'spatialconfidence' => isset($row[8]) ? $row[8] : null,
                'revised'           => isset($row[9]) ? $row[9] : null,
                'ga_guid'           => isset($row[10]) ? $row[10] : null,
                'description'       => isset($row[11]) ? $row[11] : null,
                'voltagekv'         => isset($row[12]) ? $row[12] : null,
                'locality'          => isset($row[13]) ? $row[13] : null,
                'comment'           => isset($row[14]) ? $row[14] : null,
                'x_coordinate'      => isset($row[15]) ? $row[15] : null,
                'y_coordinate'      => isset($row[16]) ? $row[16] : null,
                'type'              => 'substation'
            ];
        } else if ($type === 'power_station') {
            $lng = isset($row[0]) ? $row[0] : null;
            $lat = isset($row[1]) ? $row[1] : null;

            $properties = [
                'objectid'         => isset($row[2]) ? $row[2] : null,
                'featuretype'      => isset($row[3]) ? $row[3] : null,
                'description'      => isset($row[4]) ? $row[4] : null,
                'class'            => isset($row[5]) ? $row[5] : null,
                'name'             => isset($row[6]) ? $row[6] : null,
                'operationalstatus'=> isset($row[7]) ? $row[7] : null,
                'owner'            => isset($row[8]) ? $row[8] : null,
                'generationtype'   => isset($row[9]) ? $row[9] : null,
                'primaryfueltype'  => isset($row[10]) ? $row[10] : null,
                'primarysubfueltype'=>isset($row[11]) ? $row[11] : null,
                'generationmw'     => isset($row[12]) ? $row[12] : null,
                'generatornumber'  => isset($row[13]) ? $row[13] : null,
                'locality'         => isset($row[14]) ? $row[14] : null,
                'state'            => isset($row[15]) ? $row[15] : null,
                'spatialconfidence'=> isset($row[16]) ? $row[16] : null,
                'revised'          => isset($row[17]) ? $row[17] : null,
                'comment_'         => isset($row[18]) ? $row[18] : null,
                'ga_guid'          => isset($row[19]) ? $row[19] : null,
                'x_coordinate'     => isset($row[20]) ? $row[20] : null,
                'y_coordinate'     => isset($row[21]) ? $row[21] : null,
                'type'             => 'power_station'
            ];
        } else {
            $lng = isset($row[0]) ? $row[0] : null;
            $lat = isset($row[1]) ? $row[1] : null;

            $properties = [
                'objectid'                   => isset($row[2]) ? $row[2] : null,
                'unique_record_id'           => isset($row[3]) ? $row[3] : null,
                'ga_id'                      => isset($row[4]) ? $row[4] : null,
                'unique_site_id'             => isset($row[5]) ? $row[5] : null,
                'authority'                  => isset($row[6]) ? $row[6] : null,
                'licence_no'                 => isset($row[7]) ? $row[7] : null,
                'co_located'                 => isset($row[8]) ? $row[8] : null,
                'facility_management_type'   => isset($row[9]) ? $row[9] : null,
                'facility_infastructure_type'=> isset($row[10]) ? $row[10] : null,
                'facility_owner'             => isset($row[11]) ? $row[11] : null,
                'facility_name'              => isset($row[12]) ? $row[12] : null,
                'state'                      => isset($row[13]) ? $row[13] : null,
                'address'                    => isset($row[14]) ? $row[14] : null,
                'suburb'                     => isset($row[15]) ? $row[15] : null,
                'postcode'                   => isset($row[16]) ? $row[16] : null,
                'operational_status'         => isset($row[17]) ? $row[17] : null,
                'spatial_confidence'         => isset($row[18]) ? $row[18] : null,
                'capture_method'             => isset($row[19]) ? $row[19] : null,
                'type'                       => 'waste_facility'
            ];
        }

        // Only add features with valid lat/lng
        if (!is_null($lat) && !is_null($lng) && is_numeric($lat) && is_numeric($lng)) {
            $features[] = [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [floatval($lng), floatval($lat)]
                ],
                'properties' => $properties
            ];
        }
    }

    fclose($handle);

    return [
        'type' => 'FeatureCollection',
        'features' => $features
    ];
}

/**
 * Calculate distance between two points in kilometers
 * Using Haversine formula
 */
function calculateDistance($lat1, $lng1, $lat2, $lng2) {
    $earthRadius = 6371; // Earth's radius in kilometers
    
    $latDelta = deg2rad($lat2 - $lat1);
    $lngDelta = deg2rad($lng2 - $lng1);
    
    $a = sin($latDelta / 2) * sin($latDelta / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($lngDelta / 2) * sin($lngDelta / 2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
    return $earthRadius * $c;
}

?>