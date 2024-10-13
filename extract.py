import re
import os

# Define the file path (use the correct path for your file)
file_path = os.path.realpath('./IODeviceTree.txt')

alphanumeric_pattern = r'\b[A-Z0-9]{10}\b'
email_pattern = re.compile(rb'[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}')

# Read the file content
with open(file_path, 'r') as file:
    content = file.read()

match = re.search(r'IOPlatformSerialNumber"\s*=\s*"([^"]+)"', content)
serial_number = match.group(1)
print("Serial Number:",serial_number)

# Search for the 'nvram-proxy-data' key and capture the binary data
match = re.search(r'"nvram-proxy-data"\s+=\s+<([0-9a-fA-F]+)>', content)

# If the key is found, extract the data
if match:

    binary_data = match.group(1)
    # Convert the hex string to bytes
    binary_bytes = bytes.fromhex(binary_data)
    ascii_string = binary_bytes.decode('utf-8', errors='ignore')

    email_matches = re.findall(email_pattern, binary_bytes)
    print("Apple ID",email_matches)

    #print("Binary data:", ascii_string)
else:
    print("nvram-proxy-data key not found")