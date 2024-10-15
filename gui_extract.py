import re
import os
import binascii
import plistlib
import tkinter as tk
from tkinter import messagebox, filedialog

# Function to open file dialog and select file
def select_file():
    file_path = filedialog.askopenfilename(title="Select file", filetypes=(("Text files", "*.txt"), ("All files", "*.*")))
    if file_path:
        process_file(file_path)

# Function to process the selected file
def process_file(file_path):
    with open(file_path, 'r') as file:
        content = file.read()

    # Check for what model
    match = re.search(r'model"\s*=\s*<"([^"]+)"', content)
    model = match.group(1) if match else "Not found"

    # Check for serial number
    match = re.search(r'IOPlatformSerialNumber"\s*=\s*"([^"]+)"', content)
    serial_number = match.group(1) if match else "Not found"

    # Search for the 'fmm-mobileme-token-FMM' key and capture the binary data
    match = re.search(r'"fmm-mobileme-token-FMM"\s+=\s+<([0-9a-fA-F]+)>', content)

    if match:
        hex_data = match.group(1)
        binary_data = binascii.unhexlify(hex_data)
        plist_data = plistlib.loads(binary_data)

        user_info = plist_data['userInfo']
        first_name = user_info['InUseOwnerFirstName']
        last_name = user_info['InUseOwnerLastName']
        apple_id = plist_data['username']
    else:
        first_name = last_name = apple_id = "Not found"

    # Display the information in a popup message box
    info_text = f"Model: {model}\nSerial Number: {serial_number}\nFirst Name: {first_name}\nLast Name: {last_name}\nApple ID: {apple_id}"
    messagebox.showinfo("Device Information", info_text)

# Create the GUI application
root = tk.Tk()
root.title("Device Information")

# Add a button to select the file
select_button = tk.Button(root, text="Select File", command=select_file)
select_button.pack(pady=10)

# Run the GUI application
root.mainloop()

