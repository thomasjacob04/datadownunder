import os
import csv
import json

# Define input and output paths
script_dir = os.path.dirname(__file__)
commercial_dir = os.path.join(script_dir, 'commercial')
output_geojson_path = os.path.join(script_dir, 'land_values.geojson')

features = []

# Iterate through all files in the commercial directory
for filename in os.listdir(commercial_dir):
    if filename.endswith('.csv'):
        filepath = os.path.join(commercial_dir, filename)
        with open(filepath, mode='r', encoding='utf-8') as csvfile:
            csv_reader = csv.DictReader(csvfile)
            for row in csv_reader:
                try:
                    latitude = float(row['Latitude'])
                    longitude = float(row['Longitude'])

                    # Create a GeoJSON Feature
                    feature = {
                        "type": "Feature",
                        "geometry": {
                            "type": "Point",
                            "coordinates": [longitude, latitude]
                        },
                        "properties": {
                            "LGA": row.get('LGA'),
                            "Suburb": row.get('Suburb'),
                            "Postcode": row.get('Postcode'),
                            "Category": row.get('Category'),
                            "Land Value": row.get('Land Value'),
                            "Date of Valuation": row.get('Date of Valuation')
                        }
                    }
                    features.append(feature)
                except (ValueError, KeyError) as e:
                    print(f"Skipping row due to missing or invalid data in {filename}: {row} - Error: {e}")

# Create a GeoJSON FeatureCollection
geojson_collection = {
    "type": "FeatureCollection",
    "features": features
}

# Write the GeoJSON to a file
with open(output_geojson_path, 'w', encoding='utf-8') as f:
    json.dump(geojson_collection, f, ensure_ascii=False, indent=4)

print(f"GeoJSON file created successfully at: {output_geojson_path}")
