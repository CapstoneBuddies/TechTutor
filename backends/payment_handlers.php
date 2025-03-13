<?php
require_once 'backends/main.php';

/**
 * Handle payment creation request
 * @param mysqli $conn Database connection
 */
function handleCreatePayment($conn) {
    $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
    $description = isset($_POST['description']) ? $_POST['description'] : '';
    $paymentMethod = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';

    if ($amount < 100) {
        echo json_encode(['success' => false, 'message' => 'Minimum amount is ₱100']);
        exit;
    }

    try {
        $payMongo = new PayMongoHelper();
        
        // Create payment intent
        $paymentIntent = $payMongo->createPaymentIntent($amount, $description);
        
        if (isset($paymentIntent['error'])) {
            echo json_encode(['success' => false, 'message' => 'Failed to create payment. Please try again.']);
            exit;
        }

        // Store payment intent in database
        $query = "INSERT INTO paymongo_transactions (user_id, payment_intent_id, amount, description, payment_method_type) 
                 VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('isdss', 
            $_SESSION['user'],
            $paymentIntent['data']['id'],
            $amount,
            $description,
            $paymentMethod
        );
        $stmt->execute();

        if ($paymentMethod === 'card') {
            echo json_encode([
                'success' => true,
                'clientKey' => $paymentIntent['data']['id']
            ]);
        } else {
            // For e-wallets, create source and return checkout URL
            $source = $payMongo->createSource([
                'type' => $paymentMethod,
                'amount' => $amount * 100,
                'currency' => 'PHP',
                'redirect' => [
                    'success' => BASE . 'payment-success',
                    'failed' => BASE . 'payment-failed'
                ]
            ]);

            if (isset($source['data']['attributes']['redirect']['checkout_url'])) {
                echo json_encode([
                    'success' => true,
                    'checkoutUrl' => $source['data']['attributes']['redirect']['checkout_url']
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create payment link']);
            }
        }
    } catch (Exception $e) {
        log_error($e->getMessage(), 'payment_error.log');
        echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
    }
}

/**
 * Handle card payment processing
 * @param mysqli $conn Database connection
 */
function handleCardPayment($conn) {
    $clientKey = isset($_POST['client_key']) ? $_POST['client_key'] : '';
    $cardNumber = isset($_POST['card_number']) ? $_POST['card_number'] : '';
    $expiryDate = isset($_POST['expiry_date']) ? $_POST['expiry_date'] : '';
    $cvv = isset($_POST['cvv']) ? $_POST['cvv'] : '';

    try {
        $payMongo = new PayMongoHelper();
        
        // Create payment method
        $paymentMethod = $payMongo->createPaymentMethod([
            'type' => 'card',
            'details' => [
                'card_number' => str_replace(' ', '', $cardNumber),
                'exp_month' => substr($expiryDate, 0, 2),
                'exp_year' => '20' . substr($expiryDate, -2),
                'cvc' => $cvv
            ]
        ]);

        if (isset($paymentMethod['error'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid card details']);
            exit;
        }

        // Attach payment method to intent
        $result = $payMongo->attachPaymentMethod($clientKey, $paymentMethod['data']['id']);

        if (isset($result['data']['attributes']['status']) && $result['data']['attributes']['status'] === 'succeeded') {
            // Update transaction status
            $query = "UPDATE paymongo_transactions SET status = 'succeeded', updated_at = CURRENT_TIMESTAMP 
                     WHERE payment_intent_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('s', $clientKey);
            $stmt->execute();

            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Payment failed. Please try again.']);
        }
    } catch (Exception $e) {
        log_error($e->getMessage(), 'payment_error.log');
        echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
    }
}
?>
