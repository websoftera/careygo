#!/usr/bin/env python3
"""
Reads DTDC Pincode TAT Excel file and generates pincodes.sql for import into pincode_tat table.
"""

import sys
import os

# Install openpyxl if not available
try:
    import openpyxl
except ImportError:
    print("openpyxl not found. Installing...")
    os.system(f"{sys.executable} -m pip install openpyxl")
    import openpyxl

try:
    import pandas as pd
except ImportError:
    print("pandas not found. Installing...")
    os.system(f"{sys.executable} -m pip install pandas openpyxl")
    import pandas as pd

EXCEL_PATH = r"C:\Users\DELL\Downloads\DTDC_Pincode_TAT_Details_Master_V0_09 April 2026.xlsx"
SHEET_NAME = "Pincode Records"
OUTPUT_SQL = r"C:\xampp\htdocs\careygo\database\pincodes.sql"
BATCH_SIZE = 500

# Region → zone + TAT mapping
REGION_MAP = {
    "within city":          ("within_city",   1, 1, 1, 2),
    "within state":         ("within_state",  2, 1, 1, 3),
    "metro":                ("metro",          2, 1, 1, 4),
    "rest of india":        ("rest_of_india",  4, 2, 3, 6),
    "special destination":  ("rest_of_india",  7, 3, 5, 10),
}
DEFAULT_TAT = ("rest_of_india", 4, 2, 3, 6)

def get_zone_tat(region_val):
    if not region_val or str(region_val).strip().lower() in ("", "nan", "none"):
        return DEFAULT_TAT
    key = str(region_val).strip().lower()
    return REGION_MAP.get(key, DEFAULT_TAT)

def title_case(val):
    if not val or str(val).strip().lower() in ("", "nan", "none"):
        return ""
    return str(val).strip().title()

def escape_sql(val):
    return val.replace("'", "''")

def is_serviceable(pickup_val, delivery_val):
    p = str(pickup_val).strip() if pickup_val and str(pickup_val).lower() not in ("nan", "none", "") else ""
    d = str(delivery_val).strip() if delivery_val and str(delivery_val).lower() not in ("nan", "none", "") else ""
    if p or d:
        return 1
    return 0

def main():
    print(f"Reading Excel: {EXCEL_PATH}")
    df = pd.read_excel(EXCEL_PATH, sheet_name=SHEET_NAME, dtype=str)

    # Normalize column names (strip whitespace)
    df.columns = [c.strip() for c in df.columns]
    print(f"Columns found: {list(df.columns)}")
    print(f"Total rows in sheet: {len(df)}")

    # Identify columns (case-insensitive match)
    col_map = {c.lower(): c for c in df.columns}

    def get_col(candidates):
        for name in candidates:
            if name.lower() in col_map:
                return col_map[name.lower()]
        return None

    col_pincode  = get_col(["pincode", "pin code", "pin"])
    col_state    = get_col(["state"])
    col_district = get_col(["district", "city"])
    col_region   = get_col(["region", "Region"])
    col_pickup   = get_col(["pickupstatus", "pickup status", "pickUpStatus"])
    col_delivery = get_col(["deliverystatus", "delivery status", "deliveryStatus"])

    print(f"  pincode col  : {col_pincode}")
    print(f"  state col    : {col_state}")
    print(f"  district col : {col_district}")
    print(f"  region col   : {col_region}")
    print(f"  pickup col   : {col_pickup}")
    print(f"  delivery col : {col_delivery}")

    if not col_pincode:
        print("ERROR: Could not find pincode column!")
        sys.exit(1)

    CREATE_TABLE = """-- Auto-generated pincodes.sql
-- Source: DTDC_Pincode_TAT_Details_Master_V0_09 April 2026.xlsx
-- Generated: 2026-04-16

CREATE TABLE IF NOT EXISTS `pincode_tat` (
    `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `pincode`      VARCHAR(10)   NOT NULL,
    `city`         VARCHAR(100)  NOT NULL,
    `state`        VARCHAR(100)  NOT NULL,
    `zone`         VARCHAR(50)   DEFAULT NULL,
    `tat_standard` TINYINT UNSIGNED NOT NULL DEFAULT 3,
    `tat_premium`  TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `tat_air`      TINYINT UNSIGNED NOT NULL DEFAULT 2,
    `tat_surface`  TINYINT UNSIGNED NOT NULL DEFAULT 5,
    `serviceable`  TINYINT(1)    NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `idx_pincode` (`pincode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

"""

    rows_processed = 0
    skipped = 0
    zone_counts = {}
    batches = []
    current_batch = []

    for idx, row in df.iterrows():
        pincode_val = str(row[col_pincode]).strip() if col_pincode and str(row[col_pincode]).lower() not in ("nan", "none") else ""
        if not pincode_val or pincode_val == "":
            skipped += 1
            continue

        state_val    = title_case(row[col_state])    if col_state    else ""
        district_val = title_case(row[col_district]) if col_district else ""
        region_val   = str(row[col_region]).strip()  if col_region and str(row[col_region]).lower() not in ("nan", "none") else ""
        pickup_val   = row[col_pickup]   if col_pickup   else ""
        delivery_val = row[col_delivery] if col_delivery else ""

        zone, tat_std, tat_prem, tat_air, tat_surf = get_zone_tat(region_val)
        serviceable = is_serviceable(pickup_val, delivery_val)

        # Escape quotes
        pincode_sql  = escape_sql(pincode_val)
        city_sql     = escape_sql(district_val)
        state_sql    = escape_sql(state_val)

        value = f"('{pincode_sql}', '{city_sql}', '{state_sql}', '{zone}', {tat_std}, {tat_prem}, {tat_air}, {tat_surf}, {serviceable})"
        current_batch.append(value)
        rows_processed += 1
        zone_counts[zone] = zone_counts.get(zone, 0) + 1

        if len(current_batch) >= BATCH_SIZE:
            batches.append(current_batch)
            current_batch = []

    if current_batch:
        batches.append(current_batch)

    # Write SQL file
    print(f"\nWriting SQL to: {OUTPUT_SQL}")
    with open(OUTPUT_SQL, "w", encoding="utf-8") as f:
        f.write(CREATE_TABLE)
        f.write(f"-- Total records: {rows_processed}\n\n")
        for batch_num, batch in enumerate(batches, 1):
            f.write(
                "INSERT IGNORE INTO `pincode_tat` "
                "(`pincode`, `city`, `state`, `zone`, `tat_standard`, `tat_premium`, `tat_air`, `tat_surface`, `serviceable`) VALUES\n"
            )
            f.write(",\n".join(batch))
            f.write(";\n\n")

    print("\n--- SUMMARY ---")
    print(f"Total rows processed : {rows_processed}")
    print(f"Rows skipped (empty) : {skipped}")
    print(f"SQL batches written  : {len(batches)} (batch size: {BATCH_SIZE})")
    print(f"\nZone distribution:")
    for zone, count in sorted(zone_counts.items(), key=lambda x: -x[1]):
        print(f"  {zone:<20} : {count:>6} records")

    sql_size = os.path.getsize(OUTPUT_SQL)
    print(f"\nOutput file size: {sql_size:,} bytes ({sql_size/1024/1024:.2f} MB)")
    print(f"SQL file written to: {OUTPUT_SQL}")

if __name__ == "__main__":
    main()
