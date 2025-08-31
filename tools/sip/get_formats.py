
import json

with open('sip_datasets.json', 'r') as f:
    data = json.load(f)

formats = set()
for dataset in data['result']['results']:
    for resource in dataset['resources']:
        formats.add(resource['format'])

for f in formats:
    print(f)
