import tarfile
import sys
import os
import re
import binascii
import plistlib
import shutil

def extract_and_parse(tar_path):
    # Extract the file name from the full path
    file_name = os.path.basename(tar_path)

    # Ensure the file starts with "sysdiagnose" and ends with ".tar.gz"
    if not file_name.startswith("sysdiagnose") or not file_name.endswith(".tar.gz"):
        print("Error: The file must start with 'sysdiagnose' and end with '.tar.gz'.")
        return

    # Define the path of the file to extract within the tarball
    extracted_dir = file_name.rstrip(".tar.gz")
    file_to_extract = os.path.join(extracted_dir, "ioreg/IODeviceTree.txt")

    # Check if the tarball exists
    if not os.path.exists(tar_path):
        print(f"Error: File '{tar_path}' not found.")
        return

    # Extract the specified file
    try:
        with tarfile.open(tar_path, "r:gz") as tar:
            if file_to_extract not in tar.getnames():
                print(f"Error: '{file_to_extract}' not found in the tarball.")
                return
            tar.extract(file_to_extract, path="./")  # Extract to current directory
    except Exception as e:
        print(f"Error extracting file: {e}")
        return

    # Define the path of the extracted file
    extracted_file_path = os.path.realpath(file_to_extract)

    # Parse the extracted file
    try:
        with open(extracted_file_path, "r") as file:
            content = file.read()

        # Parse for the model
        match = re.search(r'model"\s*=\s*<"([^"]+)"', content)
        if match:
            model = match.group(1)
            print("Model:", model)

        # Parse for the serial number
        match = re.search(r'IOPlatformSerialNumber"\s*=\s*"([^"]+)"', content)
        if match:
            serial_number = match.group(1)
            print("Serial Number:", serial_number)

        # Parse for the 'fmm-mobileme-token-FMM' key and binary data
        match = re.search(r'"fmm-mobileme-token-FMM"\s+=\s+<([0-9a-fA-F]+)>', content)
        if match:
            hex_data = match.group(1)
            binary_data = binascii.unhexlify(hex_data)
            plist_data = plistlib.loads(binary_data)

            user_info = plist_data.get('userInfo', {})
            print(f"First Name: {user_info.get('InUseOwnerFirstName', 'N/A')}")
            print(f"Last Name: {user_info.get('InUseOwnerLastName', 'N/A')}")

            apple_id = plist_data.get('username', 'N/A')
            print("Apple ID:", apple_id)
    except Exception as e:
        print(f"Error parsing file: {e}")
    finally:
        # Clean up extracted directory
        if os.path.exists(extracted_dir):
            try:
                shutil.rmtree(extracted_dir)
            except Exception as e:
                print(f"Error cleaning up directory: {e}")

if __name__ == "__main__":
    if len(sys.argv) != 2:
        print("Usage: python extract.py <path_to_tarball>")
    else:
        tarball_path = sys.argv[1]
        extract_and_parse(tarball_path)

