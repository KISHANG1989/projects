import csv
import random

# Headers based on India Post / data.gov.in format
HEADERS = [
    "pincode", "officename", "pincode_type", "deliverystatus", "divisionname",
    "regionname", "circlename", "taluk", "districtname", "statename",
    "telephone", "related_suboffice", "related_headoffice", "longitude", "latitude"
]

# Sample data pools to mix and match
OFFICE_NAMES = ["Central", "North", "South", "East", "West", "Market", "Industrial Area", "Village", "Town Hall", "GPO"]
DISTRICTS = [
    ("New Delhi", "Delhi"),
    ("Mumbai", "Maharashtra"),
    ("Bangalore", "Karnataka"),
    ("Chennai", "Tamil Nadu"),
    ("Kolkata", "West Bengal"),
    ("Jaipur", "Rajasthan"),
    ("Lucknow", "Uttar Pradesh")
]
TALUKS = ["Tehsil A", "Tehsil B", "Block C", "Mandal D"]
TYPES = ["Sub Post Office", "Branch Post Office", "Head Post Office"]
DELIVERY = ["Delivery", "Non-Delivery"]

def generate_row():
    city, state = random.choice(DISTRICTS)
    pincode = f"{random.randint(11, 99)}{random.randint(10, 99)}{random.randint(10, 99)}"
    office = f"{city} {random.choice(OFFICE_NAMES)}"

    return {
        "pincode": pincode,
        "officename": office,
        "pincode_type": random.choice(TYPES),
        "deliverystatus": random.choice(DELIVERY),
        "divisionname": f"{city} Division",
        "regionname": f"{city} Region",
        "circlename": f"{state} Circle",
        "taluk": random.choice(TALUKS),
        "districtname": city,
        "statename": state,
        "telephone": "011-23456789",
        "related_suboffice": "NA",
        "related_headoffice": f"{city} GPO",
        "longitude": "",
        "latitude": ""
    }

def main():
    filepath = "pincode-portal/data/india_pincodes_sample.csv"
    with open(filepath, "w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(f, fieldnames=HEADERS)
        writer.writeheader()

        # Fixed predictable row for testing
        writer.writerow({
            "pincode": "110001",
            "officename": "New Delhi G.P.O.",
            "pincode_type": "Head Post Office",
            "deliverystatus": "Delivery",
            "divisionname": "New Delhi Central",
            "regionname": "Delhi",
            "circlename": "Delhi",
            "taluk": "New Delhi",
            "districtname": "New Delhi",
            "statename": "Delhi",
            "telephone": "011-23366666",
            "related_suboffice": "NA",
            "related_headoffice": "NA",
            "longitude": "77.2167",
            "latitude": "28.6333"
        })

        # Generate 99 random rows
        for _ in range(99):
            writer.writerow(generate_row())

    print(f"Generated {filepath} with 100 rows.")

if __name__ == "__main__":
    main()
