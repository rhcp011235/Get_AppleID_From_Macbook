import tarfile
import sys
import os
import re
import binascii
import plistlib
import shutil

def safe_extract(tar, path="."):
    """Extract tar files safely by stripping leading slashes from file paths."""
    def safe_name(member):
        member.name = member.name.lstrip("/")  # Remove leading slash to extract correctly
        return member

    tar.extractall(path=path, members=[safe_name(m) for m in tar.getmembers()], filter="data")  # Suppress Python 3.14 warning

def extract_and_parse(tar_path):
    # Extract the file name from the full path
    file_name = os.path.basename(tar_path)

    # Ensure the file has a valid extension
    if not (file_name.endswith(".tar.gz") or file_name.endswith(".tgz")):
        print("❌ Error: The file must be a .tar.gz or .tgz archive.")
        return

    # Check if the tarball exists
    if not os.path.exists(tar_path):
        print(f"❌ Error: File '{tar_path}' not found.")
        return

    # Create a temporary directory for extraction
    extracted_dir = os.path.splitext(file_name)[0]  # Remove extension
    if os.path.exists(extracted_dir):
        shutil.rmtree(extracted_dir)  # Ensure a clean slate

    os.makedirs(extracted_dir)

    # Extract the tarball safely
    try:
        with tarfile.open(tar_path, "r:gz") as tar:
            safe_extract(tar, path=extracted_dir)
    except Exception as e:
        print(f"❌ Error extracting tarball: {e}")
        return

    # Search for the IODeviceTree.txt file inside the extracted directory
    iodevice_tree_path = None
    for root, _, files in os.walk(extracted_dir):
        if "IODeviceTree.txt" in files:
            iodevice_tree_path = os.path.join(root, "IODeviceTree.txt")
            break

    if not iodevice_tree_path:
        print("❌ Error: 'IODeviceTree.txt' not found in the extracted files.")
        shutil.rmtree(extracted_dir)  # Clean up before exiting
        return

    # Parse the extracted file
    try:
        with open(iodevice_tree_path, "r") as file:
            content = file.read()

        model, serial_number, first_name, last_name, apple_id = "N/A", "N/A", "N/A", "N/A", "N/A"

        # Parse for the model
        match = re.search(r'model"\s*=\s*<"([^"]+)"', content)
        if match:
            model = match.group(1)

        # Parse for the serial number
        match = re.search(r'IOPlatformSerialNumber"\s*=\s*"([^"]+)"', content)
        if match:
            serial_number = match.group(1)

        # Parse for the 'fmm-mobileme-token-FMM' key and binary data
        match = re.search(r'"fmm-mobileme-token-FMM"\s+=\s+<([0-9a-fA-F]+)>', content)
        if match:
            hex_data = match.group(1)
            binary_data = binascii.unhexlify(hex_data)
            plist_data = plistlib.loads(binary_data)

            user_info = plist_data.get('userInfo', {})
            first_name = user_info.get('InUseOwnerFirstName', 'N/A')
            last_name = user_info.get('InUseOwnerLastName', 'N/A')
            apple_id = plist_data.get('username', 'N/A')

        # Print output in a clean format
        print("\n🔍 **Extracted Information:**")
        print(f"📌 **Model:** {model}")
        print(f"🔢 **Serial Number:** {serial_number}")
        print(f"👤 **First Name:** {first_name}")
        print(f"👤 **Last Name:** {last_name}")
        print(f"📧 **Apple ID:** {apple_id}\n")

    except Exception as e:
        print(f"❌ Error parsing file: {e}")

    finally:
        # Clean up extracted directory
        shutil.rmtree(extracted_dir)

if __name__ == "__main__":
    if len(sys.argv) != 2:
        print("Usage: python extract.py <path_to_tarball>")
    else:
        tarball_path = sys.argv[1]
        extract_and_parse(tarball_path)

