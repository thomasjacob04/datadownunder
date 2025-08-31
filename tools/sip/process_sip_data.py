
import json
import os
import subprocess
import glob
import shutil

def check_for_ogr2ogr():
    """Checks if ogr2ogr is in the system's PATH."""
    return shutil.which('ogr2ogr') is not None

def process_sip_data():
    if not check_for_ogr2ogr():
        print("Error: ogr2ogr is not installed or not in the system's PATH.")
        print("Please install GDAL and ensure ogr2ogr is accessible.")
        return

    if not os.path.exists('staging'):
        os.makedirs('staging')
    if not os.path.exists('staging/geojson'):
        os.makedirs('staging/geojson')

    with open('sip_datasets.json', 'r') as f:
        data = json.load(f)

    for dataset in data['result']['results']:
        for resource in dataset['resources']:
            if resource['format'] in ['zip mapinfo', 'zipped mapinfo']:
                url = resource['url']
                name = resource['name']
                zip_path = os.path.join('staging', name)
                
                print(f'Downloading {name} from {url}')
                subprocess.run(['curl', '-L', '-o', zip_path, url])

                print(f'Unzipping {zip_path}')
                subprocess.run(['tar', '-xf', zip_path, '-C', 'staging'])

                tab_files = glob.glob(os.path.join('staging', '**', '*.tab'), recursive=True)
                for tab_file in tab_files:
                    geojson_file = os.path.join('staging', 'geojson', os.path.basename(tab_file).replace('.tab', '.geojson'))
                    print(f'Converting {tab_file} to {geojson_file}')
                    subprocess.run(['ogr2ogr', '-f', 'GeoJSON', geojson_file, tab_file])

    print('Merging GeoJSON files')
    geojson_files = glob.glob(os.path.join('staging', 'geojson', '*.geojson'))
    features = []
    for geojson_file in geojson_files:
        with open(geojson_file, 'r') as f:
            try:
                geojson_data = json.load(f)
                if 'features' in geojson_data and geojson_data['features'] is not None:
                    features.extend(geojson_data['features'])
            except json.JSONDecodeError:
                print(f"Error reading {geojson_file}")

    combined_geojson = {
        'type': 'FeatureCollection',
        'features': features
    }

    with open('combined.geojson', 'w') as f:
        json.dump(combined_geojson, f)

    print('Done.')

if __name__ == '__main__':
    process_sip_data()
