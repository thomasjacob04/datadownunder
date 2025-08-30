<?php
header('Content-Type: application/json');

// Map sources to CSV files
$sources = [
    'substations' => __DIR__ . '/substations.csv',
    'waste' => __DIR__ . '/Waste_Management_Facilities.csv'
];

// Set default source
$source = 'substations';
if (!empty($_GET['source']) && array_key_exists($_GET['source'], $sources)) {
    $source = $_GET['source'];
}

$csvFile = $sources[$source];
$rows = [];

if (($handle = fopen($csvFile, "r")) !== FALSE) {
    $headers = fgetcsv($handle); // get column names

    while (($data = fgetcsv($handle)) !== FALSE) {
        // Handle lines shorter than headers
        if (count($data) < count($headers)) {
            $data = array_pad($data, count($headers), "");
        }
        $row = array_combine($headers, $data);

        // Filtering
        $include = true;
        foreach ($_GET as $key => $val) {
            if ($key === 'source') continue; // already handled
            // Case-insensitive match, skip empty filter value
            if ($val !== '' && isset($row[$key]) && stripos($row[$key], $val) === false) {
                $include = false;
                break;
            }
        }
        if ($include) {
            $rows[] = $row;
        }
    }
    fclose($handle);
}

echo json_encode($rows, JSON_PRETTY_PRINT);
?>