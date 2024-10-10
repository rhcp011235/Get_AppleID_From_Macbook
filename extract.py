import re

# Define the file path (use the correct path for your file)
file_path = "./IODeviceTree.txt"

# Read the file content
with open(file_path, 'r') as file:
    content = file.read()

# Search for the 'nvram-proxy-data' key and capture the binary data
match = re.search(r'"nvram-proxy-data"\s+=\s+<([0-9a-fA-F]+)>', content)

# If the key is found, extract the data
if match:
    binary_data = match.group(1)
    # Convert the hex string to bytes
    binary_bytes = bytes.fromhex(binary_data)
    print("Binary data:", binary_bytes)
else:
    print("nvram-proxy-data key not found")

