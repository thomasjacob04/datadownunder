import requests
import pandas as pd
from bs4 import BeautifulSoup
import os

def extract_land_values(lga_code, lga_name, base_date="01072024"):
    base_url = "https://www.valuergeneral.nsw.gov.au/land_value_summaries/lga.php"
    url = f"{base_url}?lga={lga_code}&base_date={base_date}"

    print(f"Fetching data for {lga_name} (LGA Code: {lga_code}) from {url}")

    try:
        response = requests.get(url)
        response.raise_for_status()  # Raise an exception for HTTP errors
    except requests.exceptions.RequestException as e:
        print(f"Error fetching data for {lga_name}: {e}")
        return

    soup = BeautifulSoup(response.content, 'html.parser')

    # Save raw HTML content
    output_dir = "downloaded_html"
    os.makedirs(output_dir, exist_ok=True)
    html_filename = os.path.join(output_dir, f"{lga_name}.html")
    with open(html_filename, "wb") as f:
        f.write(response.content)
    print(f"Downloaded HTML for {lga_name} to {html_filename}")


def main():
    # Create directories if they don't exist
    os.makedirs("downloaded_html", exist_ok=True)

    lga_codes = []
    try:
        with open("LGAcodes.txt", "r") as f:
            for line in f:
                parts = line.strip().split(" ", 1)
                if len(parts) == 2:
                    lga_codes.append((parts[0], parts[1]))
    except FileNotFoundError:
        print("LGAcodes.txt not found. Please make sure the file is in the same directory as the script.")
        return

    for code, name in lga_codes:
        extract_land_values(code, name)

if __name__ == "__main__":
    main()
