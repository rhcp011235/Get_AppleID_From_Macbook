# Get_AppleID_From_Macbook

Get the Apple ID and other useful information from a locked MacBook (Recovery mode).

You will have to watch the video on how to get the file off the MacBook. After that, all you need is the `IODeviceTree.txt` file.

Feel free to send pull requests to actually make this work 100% - This is a simple proof of concept (POC).

Since I have been getting asked how to use this tool and I think it's pretty easy, I've decided to make a small tutorial.

## Tutorial

### Step 1: Get the Sysdiag file from the Mac you are looking to extract the info from

1. Set up the required USB drive.
2. To enter Recovery Mode on your MacBook with M1 or M2 chips, follow these steps:
    - Shut down your MacBook completely.
    - Press and hold the power button until you see the startup options window. This may take around 10 seconds.
    - Once the options appear, click on "Options", then click "Continue."
    - At this point, you will hold down: `Control + Option + Command + .` and `Shift` (All at the same time).
    - A popup will appear which will ask you to save the file.
    - It will save `XXXXXX.tgz` (This is a tar gzip that you will need to open).

You are now done on this Mac and you can move over to a Windows PC (that has Python installed) or another Mac.

### Step 2: Unzip/Untar the file

On a Mac, run:
```sh
tar -xvvzf filename.tgz
```
Find the `IODeviceTree.txt` file and place it in the same spot as the `extract.py`. Run the following command:
```sh
python3 extract.py
```
Wait for the results!

Simple as that!

## Example Usage

```sh
git clone git@github.com:rhcp011235/Get_AppleID_From_Macbook.git
tar -zxvf /private/var/tmp/sysdiagnose_2025.01.11_14-41-11-0500_macOS_Mac16-6_24C101.tar.gz sysdiagnose_2025.01.11_14-41-11-0500_macOS_Mac16-6_24C101/ioreg/IODeviceTree.txt
cd sysdiagnose_2025.01.11_14-41-11-0500_macOS_Mac16-6_24C101/ioreg/
wget https://raw.githubusercontent.com/rhcp011235/Get_AppleID_From_Macbook/refs/heads/main/extract.py
python3 extract.py
```

Output:
```
Model: Mac16,6
Serial Number: XYZZZZZZZ
First Name: John
Last Name: XXXXX
Apple ID: XXXXXX
```

Since this is FREE!! and people are trying to rip you off $30 to $50 dollars, all I ask is a small donation if you use my tool!

## Donations

If you would like to donate for my open-source tools for iCloud and other stuff, I have set up a few ways:

1. https://cloud.rhcp011235.me/donate.html (Main Donation Page)
1. [Buy Me a Coffee](http://buymeacoffee.com/g6c29jy7cbN)
2. [PayPal](https://paypal.com/donate/?hosted_button_id=3YV2BWQRN6YF8)
3. BTC: `1DS731Vu2JmRgkeTsbeGG2soUo1gQzGZqm`
4. USDT (Tron Network / TRC20): `TDwbUXCo3iZGf6gji2YAMZDHcx3Nb5u7TZ`
