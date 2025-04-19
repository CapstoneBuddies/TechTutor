#!/bin/bash

# Define paths
LOG_DIR="/home/capstonebuddies/public_html/logs/"
DATE=$(date +%Y-%m-%d)
DEST_DIR="$LOG_DIR/archived/${DATE}"

# Create destination folder
mkdir -p "$DEST_DIR"

# Find and copy ONLY .log files directly inside logs/ (not subdirectories)
find "$LOG_DIR" -maxdepth 1 -type f -name "*.log" -exec cp {} "$DEST_DIR" \;

# Now delete ONLY those .log files (same filter)
find "$LOG_DIR" -maxdepth 1 -type f -name "*.log" -delete

echo "$DATE File backup has been successfull" >> "$DEST_DIR/cron.job"
