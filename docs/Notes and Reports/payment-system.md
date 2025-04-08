# Payment System Documentation

## Overview

The payment system allows users to purchase tokens using various payment methods. It includes features to:

1. Check for pending/processing transactions before allowing new purchases
2. Automatically update stale transactions via a cronjob
3. Provide clear feedback to users when they have a pending transaction

## Pending Transaction Check

The system prevents users from creating multiple pending transactions within a short timeframe (15 minutes) to avoid issues with payment processing and token balance updates.

### How it works

1. When a user attempts to create a new payment, the system checks for any existing pending/processing transactions created or updated in the last 15 minutes.
2. If a pending transaction is found, the user is shown a message with details about the pending transaction and the remaining time before they can create a new transaction.
3. The payment form is disabled and a countdown timer is displayed to indicate when the user can try again.

### Implementation Details

- The check is performed in the `checkRecentPendingTransactions()` function in `backends/handler/payment_handlers.php`.
- The check is called from both the server-side (when loading the payment page) and during payment processing.
- The payment form automatically disables inputs and shows a countdown when a pending transaction is detected.

## Stale Transaction Cleanup

Transactions that remain in a pending/processing state for too long need to be updated to maintain system integrity. This is handled by a cronjob.

### How it works

1. A cronjob runs the script `backends/cron/update_pending_transactions.php` every 3 hours.
2. The script finds all transactions that have been in pending/processing state for more than 15 minutes.
3. For each transaction, it checks the actual status with the payment provider (PayMongo) and updates accordingly.
4. Transactions that can't be verified are marked as failed with an appropriate error message.

### Setting up the Cronjob

1. Ensure the server has PHP CLI available.
2. Run the setup script:

```bash
chmod +x backends/cron/setup_transaction_cron.sh
cd backends/cron
./setup_transaction_cron.sh
```

3. The setup script will add a cronjob to run every 3 hours.
4. You can view the cronjob's output in the `logs/transaction_cron.log` file.

### Manual Execution

To manually run the cleanup process:

```bash
php backends/cron/update_pending_transactions.php
```

## Database Changes

The system leverages the existing database structure but adds:

1. Additional indexes to improve performance of transaction queries.
2. A database trigger to automatically expire very old pending transactions.
3. A view to easily identify stale transactions.

The SQL for these changes is in `sql/db_suggested.sql`.

## Troubleshooting

### Common Issues

1. **Stuck Pending Transactions**: If a transaction is stuck in pending state:
   - Check the logs for any errors
   - Run the cleanup script manually to force an update
   - Check the PayMongo dashboard for the transaction status

2. **Cronjob Not Running**: If the cleanup doesn't seem to run:
   - Check if the cron service is running: `systemctl status cron`
   - Verify the crontab entry: `crontab -l`
   - Check permissions on the PHP script: `chmod +x backends/cron/update_pending_transactions.php`
   - Check the log file for errors: `logs/transaction_cron.log`

### Logging

The payment system uses the following log types:

- `payment_error`: Records payment processing errors
- `payment_info`: Records informational messages about payments
- `info`: Records general informational messages
- `error`: Records general error messages

To view logs:

```bash
tail -f logs/error_log.txt | grep "payment_"
```

## Testing

To test the pending transaction check:

1. Create a payment but do not complete it
2. Attempt to create another payment within 15 minutes
3. Verify that the system prevents the second payment and shows the appropriate message

To test the cronjob:

1. Create a payment but do not complete it
2. Wait 15+ minutes
3. Run the cleanup script manually: `php backends/cron/update_pending_transactions.php`
4. Verify that the transaction status is updated in the database 