# Payment System Transaction Verification

This document outlines the transaction verification and cleanup system implemented to enhance our payment processing.

## System Components

The transaction verification system consists of three main components:

1. **Pre-payment checks** to prevent duplicate or conflicting transactions
2. **PayMongo API verification** to validate transaction status directly with the payment processor
3. **Automated cleanup** via cronjob to handle stale transactions

## How It Works

### 1. Pre-payment Checks

Before allowing a user to create a new payment, the system checks:

- If the user has any pending/processing transactions within the last 15 minutes
- If the user has any successfully completed transactions within the last 30 minutes

These checks help prevent:
- Double payments due to page refreshes
- Concurrent payment attempts that could lead to confusion
- Accidental duplicate purchases

### 2. PayMongo API Verification

The system directly verifies transaction status with PayMongo in two situations:

1. When checking for recent successful transactions (to confirm it was truly successful)
2. During the cleanup process to update transaction statuses in our database

The PayMongo API integration ensures our database accurately reflects the actual payment status, even if webhooks fail or the user abandoned the payment process.

### 3. Automated Cleanup (Cronjob)

The cronjob script (`update_pending_transactions.php`) runs every 3 hours to:

1. Find stale pending/processing transactions (over 15 minutes old)
2. Check their status with PayMongo
3. Update the transaction record in the database
4. For successful transactions, add tokens to the user's balance
5. Log all actions for monitoring

## Implementation Details

### Files Modified

1. `backends/handler/payment_handlers.php`
   - Enhanced `checkRecentPendingTransactions()` to check both pending and successful transactions
   - Updated `handleCreatePayment()` to handle recent successful transactions and add an ignore option

2. `pages/payment.php`
   - Added UI alerts for both pending and recent successful transactions
   - Added JavaScript to handle user choices regarding potential duplicate payments
   - Implemented countdown timer for pending transactions

### New Files Created

1. `backends/cronjobs/update_pending_transactions.php`
   - Main script for checking and updating stale transactions

2. `backends/cronjobs/setup_transaction_cron.sh`
   - Helper script to set up the cronjob

3. Documentation files (this file and README.md)

## Avoiding Double Charges

The system is specifically designed to prevent double charges by:

1. **Verification before submission**: Checking for recent successful transactions before submitting a new payment
2. **User confirmation**: Requiring explicit user confirmation if they want to proceed with a payment despite a recent success
3. **PayMongo validation**: Verifying transaction status with PayMongo to ensure accuracy
4. **Automatic cleanup**: Ensuring stale transactions are properly updated

## User Experience Flow

1. **Normal payment**: User visits payment page, selects payment method, and completes their purchase
2. **Pending transaction**: User is shown an alert with countdown timer and cannot start a new payment
3. **Recent successful transaction**: User is warned about the recent success and given the option to proceed or check their transaction history

## Running the Cronjob

The cronjob can be set up using the provided shell script:

```bash
cd backends/cronjobs
chmod +x setup_transaction_cron.sh
./setup_transaction_cron.sh
```

Or run manually for testing:

```bash
php backends/cronjobs/update_pending_transactions.php
```

## Monitoring

All transaction verification and cleanup actions are logged to the error log with specific tags:

- `payment_error`: For errors during payment processing
- `payment_info`: For general payment information
- `info`: For general information
- `tokens`: For token balance updates
- `error`: For general errors

You can monitor these logs using:

```bash
tail -f logs/error_log.txt | grep "payment_"
```

## Security Considerations

- The cronjob script verifies it's being run from the CLI to prevent web access
- Database transactions are used for token balance updates to ensure atomicity
- All API communications use secure channels and proper authentication
- Sensitive payment data is never stored in our database

## Troubleshooting

If you encounter issues with the transaction verification system:

1. **Missing token updates**: Check if the cronjob is running properly (`crontab -l`)
2. **Stuck pending transactions**: Check the error logs for API communication issues
3. **Double payments**: Ensure the pre-payment checks are working by reviewing the logs

## Further Improvements

Potential future enhancements:

1. Email notifications for successful transactions detected by the cronjob
2. Admin dashboard for monitoring stale transactions
3. More granular control over the time windows for transaction checking 