import re
import os
import binascii
import plistlib

# Define the file path (use the correct path for your file)
file_path = os.path.realpath('./IODeviceTree.txt')

# Read the file content
with open(file_path, 'r') as file:
    content = file.read()

# Check for what model
match = re.search(r'model"\s*=\s*<"([^"]+)"', content)
model = match.group(1)
print("Model:",model)

# Check for serial number
match = re.search(r'IOPlatformSerialNumber"\s*=\s*"([^"]+)"', content)
serial_number = match.group(1)
print("Serial Number:",serial_number)


# Search for the 'fmm-mobileme-token-FMM' key and capture the binary data
match = re.search(r'"fmm-mobileme-token-FMM"\s+=\s+<([0-9a-fA-F]+)>', content)

if match:
    hex_data = match.group(1)
    binary_data = binascii.unhexlify(hex_data)
    plist_data = plistlib.loads(binary_data)
    
    user_info = plist_data['userInfo']
    print(f"First Name: {user_info['InUseOwnerFirstName']}")
    print(f"Last Name: {user_info['InUseOwnerLastName']}")

    apple_id = plist_data['username']
    print("Apple ID:", apple_id)