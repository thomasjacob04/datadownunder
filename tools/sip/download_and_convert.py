
import requests
from bs4 import BeautifulSoup
import os
import subprocess
import zipfile
import json
import glob

BASE_URL = "https://data.gov.au"
SEARCH_URL = "https://data.gov.au/data/dataset/?q=SIP+register&res_format=zip+mapinfo"

def get_dataset_links():
    response = requests.get(SEARCH_URL)
    soup = BeautifulSoup(response.content, 'html.parser')
    dataset_links = []
    for a in soup.find_all('a', href=True):
        if '/data/dataset/' in a['href'] and 'format' not in a['href']:
            dataset_links.append(BASE_URL + a['href'])
    return list(set(dataset_links))

if __name__ == '__main__':
    links = get_dataset_links()
    print(f"Found {len(links)} dataset links.")
