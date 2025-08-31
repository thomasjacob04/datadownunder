import json

input_geojson_path = 'C:/Users/sibun/Documents/dev/GovHack/DataDownUnder/tools/land_values/LGA_boundaries.geojson'
lgacodes_path = 'C:/Users/sibun/Documents/dev/GovHack/DataDownUnder/tools/land_values/LGAcodes.txt'
output_geojson_path = 'C:/Users/sibun/Documents/dev/GovHack/DataDownUnder/tools/land_values/filtered_LGA_boundaries.geojson'

def calculate_polygon_centroid(coords):
    # For a single polygon (list of rings)
    # We only consider the exterior ring for centroid calculation
    exterior_ring = coords[0]
    x_coords = [p[0] for p in exterior_ring]
    y_coords = [p[1] for p in exterior_ring]
    return [sum(x_coords) / len(x_coords), sum(y_coords) / len(y_coords)]

def calculate_multipolygon_centroid(coords):
    # For a multi-polygon (list of polygons)
    all_x = []
    all_y = []
    for polygon_coords in coords:
        # Each polygon_coords is a list of rings, take the exterior ring
        exterior_ring = polygon_coords[0]
        all_x.extend([p[0] for p in exterior_ring])
        all_y.extend([p[1] for p in exterior_ring])
    return [sum(all_x) / len(all_x), sum(all_y) / len(all_y)]

def filter_geojson_by_lga_name(input_path, codes_path, output_path):
    """
    Reads a GeoJSON file, filters features based on LGA names from a text file,
    and writes a new GeoJSON file containing only centroid points with abb_name.
    """
    try:
        # Read LGA names from the text file
        with open(codes_path, 'r', encoding='utf-8') as f:
            lga_names_to_keep = {line.strip().split(' ', 1)[1] for line in f if line.strip() and ' ' in line}

        # Read the input GeoJSON file
        with open(input_path, 'r', encoding='utf-8') as f:
            geojson_data = json.load(f)

        filtered_features = []
        if geojson_data.get('type') == 'FeatureCollection':
            for feature in geojson_data.get('features', []):
                abb_name = feature.get('properties', {}).get('abb_name')
                if abb_name and abb_name in lga_names_to_keep:
                    centroid = None
                    geometry = feature.get('geometry')
                    if geometry:
                        geom_type = geometry.get('type')
                        geom_coords = geometry.get('coordinates')
                        if geom_type == 'Polygon':
                            centroid = calculate_polygon_centroid(geom_coords)
                        elif geom_type == 'MultiPolygon':
                            centroid = calculate_multipolygon_centroid(geom_coords)

                    if centroid:
                        # Create a new Feature with Point geometry for the centroid
                        new_feature = {
                            "type": "Feature",
                            "geometry": {
                                "type": "Point",
                                "coordinates": centroid
                            },
                            "properties": {
                                "abb_name": abb_name
                            }
                        }
                        filtered_features.append(new_feature)
        elif geojson_data.get('type') == 'Feature':
            # Handle single Feature case if necessary
            abb_name = geojson_data.get('properties', {}).get('abb_name')
            if abb_name and abb_name in lga_names_to_keep:
                centroid = None
                geometry = geojson_data.get('geometry')
                if geometry:
                    geom_type = geometry.get('type')
                    geom_coords = geometry.get('coordinates')
                    if geom_type == 'Polygon':
                        centroid = calculate_polygon_centroid(geom_coords)
                    elif geom_type == 'MultiPolygon':
                        centroid = calculate_multipolygon_centroid(geom_coords)

                if centroid:
                    new_feature = {
                        "type": "Feature",
                        "geometry": {
                            "type": "Point",
                            "coordinates": centroid
                        },
                        "properties": {
                            "abb_name": abb_name
                        }
                    }
                    filtered_features.append(new_feature)
        else:
            print(f"Warning: Input GeoJSON is not a FeatureCollection or Feature. Type: {geojson_data.get('type')}")
            return

        # Create a new FeatureCollection with filtered features
        filtered_geojson = {
            "type": "FeatureCollection",
            "features": filtered_features
        }

        # Write the filtered GeoJSON to the output file
        with open(output_path, 'w', encoding='utf-8') as f:
            json.dump(filtered_geojson, f, indent=2)

        print(f"Filtered GeoJSON saved to: {output_path}")
        print(f"Total features in input: {len(geojson_data.get('features', [])) if geojson_data.get('type') == 'FeatureCollection' else 1 if geojson_data.get('type') == 'Feature' else 0}")
        print(f"Total features in output: {len(filtered_features)}")

    except FileNotFoundError as e:
        print(f"Error: One of the input files was not found. {e}")
    except json.JSONDecodeError as e:
        print(f"Error: Could not decode JSON from GeoJSON file. {e}")
    except Exception as e:
        print(f"An unexpected error occurred: {e}")

if __name__ == "__main__":
    filter_geojson_by_lga_name(input_geojson_path, lgacodes_path, output_geojson_path)