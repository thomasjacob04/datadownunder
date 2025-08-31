import os
import pandas as pd
from bs4 import BeautifulSoup

def process_html_file(html_file_path):
    lga_name = os.path.splitext(os.path.basename(html_file_path))[0]
    print(f"Processing {lga_name} from {html_file_path}")

    try:
        with open(html_file_path, "r", encoding="utf-8") as f:
            html_content = f.read()
    except Exception as e:
        print(f"Error reading {html_file_path}: {e}")
        return

    soup = BeautifulSoup(html_content, 'html.parser')

    residential_df = None
    commercial_df = None
    commercial_table_type = "commercial"

    h4_tags = soup.find_all('h4')

    for h4 in h4_tags:
        h4_text = h4.get_text()

        if "Typical residential land values" in h4_text:
            table = h4.find_next('table')
            if table:
                try:
                    residential_df = pd.read_html(str(table))[0]
                except ValueError:
                    print(f"Could not parse residential table for {lga_name}")
            continue

        elif "Typical commercial land values" in h4_text:
            commercial_table_type = "commercial"
            table = h4.find_next('table')
            if table:
                try:
                    commercial_df = pd.read_html(str(table))[0]
                except ValueError:
                    print(f"Could not parse commercial table for {lga_name}")
            continue

        elif "Typical industrial land values" in h4_text:
            commercial_table_type = "industrial"
            table = h4.find_next('table')
            if table:
                try:
                    commercial_df = pd.read_html(str(table))[0]
                except ValueError:
                    print(f"Could not parse industrial table for {lga_name}")
            continue

        elif "Typical rural land values" in h4_text:
            commercial_table_type = "rural"
            table = h4.find_next('table')
            if table:
                try:
                    commercial_df = pd.read_html(str(table))[0]
                except ValueError:
                    print(f"Could not parse rural table for {lga_name}")
            continue

    # Save data to CSV
    if residential_df is not None:
        residential_filename = os.path.join("residential", f"{lga_name}.csv")
        residential_df.to_csv(residential_filename, index=False)
        print(f"Saved residential data for {lga_name} to {residential_filename}")
    else:
        print(f"No residential data found for {lga_name}")

    if commercial_df is not None:
        commercial_filename = os.path.join("commercial", f"{lga_name}_{commercial_table_type}.csv")
        commercial_df.to_csv(commercial_filename, index=False)
        print(f"Saved {commercial_table_type} data for {lga_name} to {commercial_filename}")
    else:
        print(f"No {commercial_table_type} data found for {lga_name}")

def main():
    html_input_dir = "downloaded_html"
    os.makedirs("residential", exist_ok=True)
    os.makedirs("commercial", exist_ok=True)

    if not os.path.exists(html_input_dir):
        print(f"Error: Directory '{html_input_dir}' not found. Please run extract_data.py first to download HTML files.")
        return

    for filename in os.listdir(html_input_dir):
        if filename.endswith(".html"):
            html_file_path = os.path.join(html_input_dir, filename)
            process_html_file(html_file_path)

if __name__ == "__main__":
    main()
