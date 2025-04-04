#!/bin/bash
#
# Setup script for transaction update cronjob
# This creates a cronjob to run the update_pending_transactions.php script every 3 hours
#

# Get the absolute path to the script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PHP_SCRIPT="$SCRIPT_DIR/update_pending_transactions.php"
PROJECT_ROOT="$( cd "$SCRIPT_DIR/../.." && pwd )"
LOG_FILE="$PROJECT_ROOT/logs/transaction_cron.log"

# Check that the PHP script exists
if [ ! -f "$PHP_SCRIPT" ]; then
    echo "ERROR: The PHP script does not exist at $PHP_SCRIPT"
    exit 1
fi

# Check that PHP is installed and available
if ! command -v php &> /dev/null; then
    echo "ERROR: PHP command line is not available. Please install PHP CLI."
    exit 1
fi

# Create logs directory if it doesn't exist
mkdir -p "$PROJECT_ROOT/logs"

# Create the crontab entry - runs every 3 hours
CRON_ENTRY="0 */3 * * * php $PHP_SCRIPT >> $LOG_FILE 2>&1"
echo ""
echo "This script will add the following cron job to your crontab:"
echo "$CRON_ENTRY"
echo ""
echo "This will run the transaction update script every 3 hours and log output to $LOG_FILE"
echo ""

# Ask for confirmation
read -p "Do you want to proceed? (y/n): " CONFIRM
if [[ "$CONFIRM" != "y" && "$CONFIRM" != "Y" ]]; then
    echo "Operation cancelled."
    exit 0
fi

# Check if entry already exists
EXISTING_CRON=$(crontab -l 2>/dev/null | grep -F "$PHP_SCRIPT")
if [ ! -z "$EXISTING_CRON" ]; then
    echo "A cron job for this script already exists:"
    echo "$EXISTING_CRON"
    read -p "Do you want to replace it? (y/n): " REPLACE
    if [[ "$REPLACE" != "y" && "$REPLACE" != "Y" ]]; then
        echo "Operation cancelled."
        exit 0
    fi
    
    # Remove existing entry
    (crontab -l 2>/dev/null | grep -v -F "$PHP_SCRIPT") | crontab -
fi

# Add the new cron entry
(crontab -l 2>/dev/null; echo "$CRON_ENTRY") | crontab -
if [ $? -eq 0 ]; then
    echo "Cron job has been successfully added."
    echo "The transaction update script will run every 3 hours."
    echo "Output will be logged to: $LOG_FILE"
else
    echo "ERROR: Failed to add cron job."
    exit 1
fi

# Create empty log file if it doesn't exist
touch "$LOG_FILE"
chmod 644 "$LOG_FILE"

echo ""
echo "To manually test the script, run:"
echo "php $PHP_SCRIPT"
echo ""

exit 0 