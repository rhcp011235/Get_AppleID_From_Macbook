import os
import tarfile
import binascii
import plistlib
import re
import shutil
import tkinter as tk
from tkinter import filedialog, messagebox, scrolledtext
from tkinter import font as tkFont

def extract_and_parse(tar_path):
    # Set the extraction directory to ~/Downloads
    extracted_dir = os.path.join(os.path.expanduser("~/Downloads"), os.path.splitext(os.path.basename(tar_path))[0])

    if os.path.exists(extracted_dir):
        shutil.rmtree(extracted_dir)
    os.makedirs(extracted_dir)

    try:
        with tarfile.open(tar_path, "r:gz") as tar:
            tar.extractall(path=extracted_dir, filter='data')  # Use 'data' filter to avoid Python 3.14 warning

    except Exception as e:
        messagebox.showerror("Extraction Error", f"Error extracting tarball: {e}")
        return

    iodevice_tree_path = None
    for root, _, files in os.walk(extracted_dir):
        if "IODeviceTree.txt" in files:
            iodevice_tree_path = os.path.join(root, "IODeviceTree.txt")
            break

    if not iodevice_tree_path:
        messagebox.showerror("File Not Found", "IODeviceTree.txt not found in the extracted files.")
        shutil.rmtree(extracted_dir)
        return

    try:
        with open(iodevice_tree_path, "r") as file:
            content = file.read()

        model = re.search(r'model"\s*=\s*<"([^"]+)">', content)
        serial_number = re.search(r'IOPlatformSerialNumber"\s*=\s*"([^"]+)"', content)
        token_match = re.search(r'"fmm-mobileme-token-FMM"\s+=\s+<([0-9a-fA-F]+)>', content)

        model = model.group(1) if model else "N/A"
        serial_number = serial_number.group(1) if serial_number else "N/A"
        first_name = last_name = apple_id = "N/A"

        if token_match:
            hex_data = token_match.group(1)
            binary_data = binascii.unhexlify(hex_data)
            plist_data = plistlib.loads(binary_data)
            user_info = plist_data.get('userInfo', {})
            first_name = user_info.get('InUseOwnerFirstName', 'N/A')
            last_name = user_info.get('InUseOwnerLastName', 'N/A')
            apple_id = plist_data.get('username', 'N/A')

        results = f"Model: {model}\nSerial Number: {serial_number}\nFirst Name: {first_name}\nLast Name: {last_name}\nApple ID: {apple_id}"
        scrolled_result.delete(1.0, tk.END)
        scrolled_result.insert(tk.END, results)
    except Exception as e:
        messagebox.showerror("Parsing Error", f"Error parsing file: {e}")
    finally:
        shutil.rmtree(extracted_dir)

def browse_file():
    file_path = filedialog.askopenfilename()
    if file_path and (file_path.endswith(".tar.gz") or file_path.endswith(".tgz")):
        extract_and_parse(file_path)
    elif file_path:
        messagebox.showerror("Invalid File", "Please select a valid .tar.gz or .tgz file.")

def save_results():
    results = scrolled_result.get(1.0, tk.END).strip()
    if not results:
        messagebox.showwarning("No Data", "There is no data to save.")
        return
    file_path = filedialog.asksaveasfilename(defaultextension=".txt", filetypes=[("Text Files", "*.txt")])
    if file_path:
        with open(file_path, "w") as file:
            file.write(results)
        messagebox.showinfo("Saved", "Results saved successfully.")

# Create the main window
root = tk.Tk()
root.title("Macbook Owner Info Extractor")
root.geometry("600x450")
root.configure(bg="#2E3440")  # Dark background for the window

# Custom font
custom_font = tkFont.Font(family="Helvetica", size=12)

# Frame for buttons
button_frame = tk.Frame(root, bg="#2E3440")  # Dark background for the frame
button_frame.pack(pady=10)

# Button to select file
select_button = tk.Button(button_frame, text="Select backup/sysdiag file to read", command=browse_file, font=custom_font, bg="#5E81AC", fg="black", padx=10, pady=5)
select_button.pack(side=tk.LEFT, padx=10)

# Button to save results
save_button = tk.Button(button_frame, text="Save Results", command=save_results, font=custom_font, bg="#88C0D0", fg="black", padx=10, pady=5)
save_button.pack(side=tk.LEFT, padx=10)

# ScrolledText widget for displaying results
scrolled_result = scrolledtext.ScrolledText(root, height=10, width=70, wrap=tk.WORD, font=custom_font, bg="#ECEFF4", fg="#2E3440")
scrolled_result.pack(padx=10, pady=10)

# Run the application
root.mainloop()
