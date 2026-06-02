#!/bin/bash
# 070.trust.firefox.sh - Import certificate to Snap Firefox

CERT_FILE="./040.byrobot.fullchain.pem"

# Check if certificate exists
if [ ! -f "$CERT_FILE" ]; then
    echo "Error: $CERT_FILE not found"
    exit 1
fi

# Snap Firefox profile path (different from regular Firefox!)
FIREFOX_DIR="$HOME/snap/firefox/common/.mozilla/firefox"

if [ ! -d "$FIREFOX_DIR" ]; then
    echo "Error: Snap Firefox not found at $FIREFOX_DIR"
    echo "Please run Firefox once to create profile"
    exit 1
fi

# Get the default profile
PROFILE=$(ls -1 "$FIREFOX_DIR" | grep "\.default" | head -1)

if [ -z "$PROFILE" ]; then
    echo "No Firefox profiles found in: $FIREFOX_DIR"
    ls -la "$FIREFOX_DIR"
    exit 1
fi

PROFILE_PATH="$FIREFOX_DIR/$PROFILE"
echo "Using Snap Firefox profile: $PROFILE_PATH"

# Close Firefox if running
pkill firefox 2>/dev/null
sleep 1

# Import certificate with CA trust flag
certutil -A -n "zero.home" -t "C,," -d sql:"$PROFILE_PATH" -i "$CERT_FILE"

if [ $? -eq 0 ]; then
    echo ""
    echo "Success! Certificate imported to Snap Firefox"
    echo "Restart Firefox and visit https://zero.home:50443"
else
    echo "Import failed. Check if certutil is installed:"
    echo "sudo apt install libnss3-tools"
fi