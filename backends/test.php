<?php

// Your PayMongo secret key (replace with your actual key)
$secretKey = 'sk_test_SSL1na3A2VLJFh2NeH88Rx9u';  // e.g., 'sk_test_abc123...'

// The payment intent ID you want to check (replace with your actual payment intent ID)
$paymentIntentId = 'pi_q4bHPaKRbhtRsEuQQvhg2RKe';  // e.g., 'pi_abc123...'

// Base64 encode the secret key for Basic Authentication
$encodedSecretKey = base64_encode($secretKey . ':');

// Initialize cURL session
$ch = curl_init();

// Set the URL for PayMongo's payment intents endpoint
curl_setopt($ch, CURLOPT_URL, "https://api.paymongo.com/v1/payment_intents/$paymentIntentId");

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  // Return the response as a string
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Basic ' . $encodedSecretKey,  // Basic authentication header
]);

// Execute the cURL request and capture the response
$response = curl_exec($ch);

// Check for any cURL errors
if ($response === false) {
    echo "cURL Error: " . curl_error($ch) . "\n";
    exit;
}

// Decode the JSON response
$responseData = json_decode($response, true);

// Close the cURL session
curl_close($ch);

// Check if the 'data' field exists and contains 'attributes' with 'status'
if (isset($responseData['data']['attributes']['status'])) {
    $status = $responseData['data']['attributes']['status'];

    echo print_r($responseData,true);

    // Handle different statuses
    if ($status === 'awaiting_payment_method') {
        echo "Payment status: Awaiting payment method.\n";
    } elseif ($status === 'succeeded') {
        echo "Payment status: Payment succeeded.\n";
    } elseif ($status === 'failed') {
        echo "Payment status: Payment failed.\n";
    } else {
        echo "Payment status: $status.\n";
    }
} else {
    // If the status is not found, print the error message from PayMongo
    if (isset($responseData['errors'])) {
        foreach ($responseData['errors'] as $error) {
            echo "Error: " . $error['detail'] . "\n";
        }
    } else {
        echo "Error: Unable to retrieve payment status. Response: " . json_encode($responseData) . "\n";
    }
}

?>