# Transaction Cleanup Cronjob

This script automatically checks and updates stale pending/processing transactions in the database by verifying their status with PayMongo.

## How It Works

1. The script finds all transactions with status 'pending' or 'processing' that are over 15 minutes old
2. For each transaction, it checks with PayMongo for the actual status
3. It updates the database with the correct status
4. For successful transactions, it updates user token balances
5. It logs all actions for monitoring

## Setup Instructions

### Prerequisites

- PHP CLI (Command Line Interface) installed
- Access to crontab on your server
- Your web server needs read/write access to the logs directory

### Setting Up the Cronjob

1. Make the setup script executable:
   ```
   chmod +x setup_transaction_cron.sh
   ```

2. Run the setup script:
   ```
   ./setup_transaction_cron.sh
   ```

3. Follow the prompts to create a cronjob that runs every 3 hours

### Manual Execution

To run the script manually for testing:

```
php update_pending_transactions.php
```

### Monitoring

Check the log file to monitor the script's activity:

```
tail -f ../../logs/transaction_cron.log
```

## How to Verify It's Working

1. Find a pending transaction in your database that's older than 15 minutes
2. Run the script manually
3. Check the database to see if the transaction status has been updated
4. Review the log file for details on what the script did

## Troubleshooting

If the script doesn't work as expected:

1. Check if PHP CLI is available: `which php`
2. Verify file permissions: The script should be executable
3. Check log files for errors
4. Verify the PayMongo API is accessible and properly configured
5. Ensure database credentials are correct in the main.php file

## Security Considerations

- The script should be run with the appropriate user permissions
- Log files should be regularly rotated to avoid disk space issues
- API keys used by PayMongo should be kept secure 