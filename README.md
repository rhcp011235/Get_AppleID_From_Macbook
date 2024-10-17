# Get_AppleID_From_Macbook
Get the Apple ID and other useful information from a locked MacBook (Recovery mode)

You will have to watch the video on how to get the file off the macbook, after that, all you need is the IODeviceTree.txt file

Feel free to send PULL requests to actually make this work 100% - This is a simple POC




Since I have been getting asked how to use this tool and i think its pretty easy, ive decided to make a small tutorial.


Step 1. Get the Sysdiag file from the Mac you are looking to extract the info from.
    + Set up the required USB drive.
    + To enter Recovery Mode on your MacBook with M1 or M2 chips, follow these steps:
    + Shut down your MacBook completely.
    + Press and hold the power button until you see the startup options window. This may take around 10 seconds.
    + Once the options appear, click on "Options", then click "Continue."
    + at this point you will hold down: Control + option + command + . and shift (All at the same time)
    + A popup will appear which will ask you to save the file. 
    + It will save XXXXXX.tgz (This is a tar gzip that you will need to open)
    You are now done on this mac and you can move over to a Windwos pc (That has Python installed or another Mac)

    Step 2. Unzip/Untar the file
    on a mac, tar -xvvzf filename.tgz
    find the IODeviceTree.txt file and place it in the same spoot as the extract.py
    run the following command: 

    python3 extract.py and wait for the results!

    Simple as that!


Since this is FREE!! and people are trying to rip you off $30 to $50 dollars all i ask is a small donation if you use my tool!

Buy me a coffee (Paypal) : https://www.paypal.com/donate/?hosted_button_id=3YV2BWQRN6YF8

Buy me a coffee (Cofee) : https://buymeacoffee.com/g6c29jy7cbN

Send via USDT (binance) - 0xfaed453da202d71061e43e39b8c3621a73df74ba
