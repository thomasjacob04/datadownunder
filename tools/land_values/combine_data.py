import json
import os
import csv

# Define paths
filtered_lga_geojson_path = 'C:/Users/sibun/Documents/dev/GovHack/DataDownUnder/tools/land_values/filtered_LGA_boundaries.geojson'
commercial_dir = 'C:/Users/sibun/Documents/dev/GovHack/DataDownUnder/tools/land_values/commercial'
residential_dir = 'C:/Users/sibun/Documents/dev/GovHack/DataDownUnder/tools/land_values/residential'
output_combined_geojson_path = 'C:/Users/sibun/Documents/dev/GovHack/DataDownUnder/tools/land_values/combined_land_values.geojson'

def get_lga_name_from_filename(filename):
    # Remove .csv extension
    name_without_ext = os.path.splitext(filename)[0]
    # Split by underscore and take the first part for the LGA name
    return name_without_ext.split('_')[0]

def read_and_aggregate_csv_data(directory):
    lga_data = {}
    for filename in os.listdir(directory):
        if filename.endswith('.csv'):
            lga_name = get_lga_name_from_filename(filename)
            filepath = os.path.join(directory, filename)
            
            records = []
            with open(filepath, 'r', encoding='utf-8') as f:
                reader = csv.DictReader(f)
                for row in reader:
                    records.append(row)
            
            if lga_name not in lga_data:
                lga_data[lga_name] = []
            lga_data[lga_name].extend(records)
    return lga_data

def combine_geojson_with_csv_data(
    geojson_path, commercial_dir, residential_dir, output_path
):
    try:
        # Load filtered LGA GeoJSON
        with open(geojson_path, 'r', encoding='utf-8') as f:
            lga_geojson = json.load(f)

        # Read and aggregate commercial data
        commercial_data = read_and_aggregate_csv_data(commercial_dir)
        print(f"Aggregated commercial data for {len(commercial_data)} LGAs.")

        # Read and aggregate residential data
        residential_data = read_and_aggregate_csv_data(residential_dir)
        print(f"Aggregated residential data for {len(residential_data)} LGAs.")

        combined_features = []
        for feature in lga_geojson.get('features', []):
            abb_name = feature.get('properties', {}).get('abb_name')
            if abb_name:
                # Create a copy of properties to avoid modifying original during iteration
                new_properties = feature['properties'].copy()
                
                # Add commercial data if available
                if abb_name in commercial_data:
                    new_properties['commercial_land_values'] = commercial_data[abb_name]
                
                # Add residential data if available
                if abb_name in residential_data:
                    new_properties['residential_land_values'] = residential_data[abb_name]
                
                feature['properties'] = new_properties
                combined_features.append(feature)
            else:
                print(f"Warning: Feature without 'abb_name' property skipped: {feature}")

        # Create the final combined GeoJSON FeatureCollection
        combined_geojson = {
            "type": "FeatureCollection",
            "features": combined_features
        }

        # Write the combined GeoJSON to the output file
        with open(output_path, 'w', encoding='utf-8') as f:
            json.dump(combined_geojson, f, indent=2)

        print(f"Combined GeoJSON saved to: {output_path}")
        print(f"Total features in combined output: {len(combined_features)}")

    except FileNotFoundError as e:
        print(f"Error: One of the input files or directories was not found. {e}")
    except json.JSONDecodeError as e:
        print(f"Error: Could not decode JSON from GeoJSON file. {e}")
    except Exception as e:
        print(f"An unexpected error occurred: {e}")

if __name__ == "__main__":
    combine_geojson_with_csv_data(
        filtered_lga_geojson_path, commercial_dir, residential_dir, output_combined_geojson_path
    )
