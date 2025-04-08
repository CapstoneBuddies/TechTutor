<?php
// PayMongo Configuration
define('PAYMONGO_SECRET_KEY', $_ENV['PayMongo_SECRET']); // From .env file
define('PAYMONGO_PUBLIC_KEY', $_ENV['PayMongo_PUBLIC']); // From .env file
define('PAYMONGO_API_URL', 'https://api.paymongo.com/v1');

class PayMongoHelper {
    private $secretKey;
    private $publicKey;
    private $apiUrl;

    public function __construct() {
        $this->secretKey = PAYMONGO_SECRET_KEY;
        $this->publicKey = PAYMONGO_PUBLIC_KEY;
        $this->apiUrl = PAYMONGO_API_URL;
    }

    /**
     * Create a Payment Intent
     * @param float $amount Amount in PHP (will be converted to cents)
     * @param string $description Payment description
     * @return array Response from PayMongo
     */
    public function createPaymentIntent($amount, $description) {
        $data = [
            'data' => [
                'attributes' => [
                    'amount' => $amount * 100, // Convert to cents
                    'payment_method_allowed' => [
                        'card',
                        'gcash',
                        'grab_pay',
                        'maya'
                    ],
                    'payment_method_options' => [
                        'card' => ['request_three_d_secure' => 'any']
                    ],
                    'description' => $description,
                    'currency' => 'PHP'
                ]
            ]
        ];

        return $this->sendRequest('/payment_intents', 'POST', $data);
    }

    /**
     * Check if a payment intent with the specified ID exists and return its current status
     * @param string $paymentIntentId Payment Intent ID to check
     * @return array Status information including 'exists', 'status', and 'data'
     */
    public function checkPaymentIntentStatus($paymentIntentId) {
        if (empty($paymentIntentId)) {
            return [
                'exists' => false,
                'status' => null,
                'data' => null,
                'message' => 'No payment intent ID provided'
            ];
        }
        
        $response = $this->sendRequest("/payment_intents/{$paymentIntentId}", 'GET');
        
        // Check if the request was successful and contains data
        if (isset($response['data']) && isset($response['data']['id'])) {
            $status = $response['data']['attributes']['status'] ?? 'unknown';
            return [
                'exists' => true,
                'status' => $status,
                'is_terminal' => in_array($status, ['succeeded', 'cancelled', 'processing']),
                'data' => $response['data'],
                'message' => "Payment intent exists with status: {$status}"
            ];
        }
        
        // If we have errors, the payment intent might exist but have issues
        if (isset($response['errors'])) {
            $errorCode = $response['errors'][0]['code'] ?? '';
            $errorDetail = $response['errors'][0]['detail'] ?? 'Unknown error';
            
            // Check if the error indicates a payment intent that doesn't exist
            if (strpos($errorCode, 'resource_not_found') !== false) {
                return [
                    'exists' => false,
                    'status' => null,
                    'data' => null,
                    'message' => 'Payment intent not found'
                ];
            }
            
            // For other errors, log them but consider the payment intent might exist
            log_error("PayMongo API error when checking payment intent: {$errorDetail}", 'payment_error');
            return [
                'exists' => true, // Assume it exists but has issues
                'status' => 'error',
                'is_terminal' => false,
                'data' => null,
                'message' => $errorDetail
            ];
        }
        
        // Default fallback for unexpected responses
        return [
            'exists' => false,
            'status' => null,
            'data' => null,
            'message' => 'Could not determine payment intent status'
        ];
    }

    /**
     * Create a Source for e-wallet payments
     * @param array $sourceDetails Source details including type, amount, and redirect URLs
     * @return array Response from PayMongo
     */
    public function createSource($sourceDetails) {
        $data = [
            'data' => [
                'attributes' => [
                    'type' => $sourceDetails['type'],
                    'amount' => $sourceDetails['amount'],
                    'currency' => $sourceDetails['currency'],
                    'redirect' => [
                        'success' => $sourceDetails['redirect']['success'],
                        'failed' => $sourceDetails['redirect']['failed']
                    ],
                    'billing' => [
                        'name' => $_SESSION['first_name'] . ' ' . $_SESSION['last_name'],
                        'email' => $_SESSION['email']
                    ]
                ]
            ]
        ];

        return $this->sendRequest('/sources', 'POST', $data);
    }

    /**
     * Create a Payment Method
     * @param array $paymentDetails Payment method details
     * @return array Response from PayMongo
     */
    public function createPaymentMethod($paymentDetails) {
        // Make sure we have the right structure for the payment method
        if (!isset($paymentDetails['type'])) {
            log_error("Missing payment method type", 'payment_error');
            return ['error' => true, 'message' => 'Missing payment method type'];
        }

        // Construct the proper data structure for PayMongo API
        $data = [
            'data' => [
                'attributes' => [
                    'type' => $paymentDetails['type']
                ]
            ]
        ];

        // Add details based on the payment method type
        if ($paymentDetails['type'] === 'card' && isset($paymentDetails['details'])) {
            $data['data']['attributes']['details'] = $paymentDetails['details'];
            
            // Add billing information if available
            if (isset($_SESSION['first_name']) && isset($_SESSION['last_name']) && isset($_SESSION['email'])) {
                $data['data']['attributes']['billing'] = [
                    'name' => $_SESSION['first_name'] . ' ' . $_SESSION['last_name'],
                    'email' => $_SESSION['email']
                ];
            }
        }

        $response = $this->sendRequest('/payment_methods', 'POST', $data);
                
        return $response;
    }

    /**
     * Attach Payment Method to Payment Intent
     * @param string $paymentIntentId Payment Intent ID
     * @param string $paymentMethodId Payment Method ID
     * @return array Response from PayMongo
     */
    public function attachPaymentMethod($paymentIntentId, $paymentMethodId) {
        // First check if the payment intent exists and its status
        $statusCheck = $this->checkPaymentIntentStatus($paymentIntentId);
        
        // If payment intent already succeeded, return the success status instead of trying to attach
        if ($statusCheck['exists'] && $statusCheck['status'] === 'succeeded') {
            log_error("Payment intent {$paymentIntentId} already succeeded, skipping attachment", 'payment_info');
            return [
                'data' => [
                    'id' => $paymentIntentId,
                    'type' => 'payment_intent',
                    'attributes' => [
                        'status' => 'succeeded',
                        'last_payment_error' => null
                    ]
                ],
                'already_succeeded' => true
            ];
        }
        
        // If payment intent is in processing state, return that status
        if ($statusCheck['exists'] && $statusCheck['status'] === 'processing') {
            log_error("Payment intent {$paymentIntentId} is still processing, cannot attach payment method", 'payment_info');
            return [
                'data' => [
                    'id' => $paymentIntentId,
                    'type' => 'payment_intent',
                    'attributes' => [
                        'status' => 'processing',
                        'last_payment_error' => null
                    ]
                ],
                'already_processing' => true
            ];
        }
        
        // Proceed with normal attachment if not in a terminal state
        $data = [
            'data' => [
                'attributes' => [
                    'payment_method' => $paymentMethodId
                ]
            ]
        ];

        return $this->sendRequest("/payment_intents/{$paymentIntentId}/attach", 'POST', $data);
    }

    /**
     * Process a refund
     * @param string $paymentIntentId Payment Intent ID to refund
     * @param float $amount Amount to refund in PHP (will be converted to cents)
     * @param string $reason Reason for refund
     * @return array Response from PayMongo
     */
    public function processRefund($paymentIntentId, $amount, $reason = '') {
        $data = [
            'data' => [
                'attributes' => [
                    'amount' => $amount * 100, // Convert to cents
                    'payment_intent' => $paymentIntentId,
                    'reason' => $reason
                ]
            ]
        ];

        return $this->sendRequest('/refunds', 'POST', $data);
    }

    /**
     * Send request to PayMongo API
     * @param string $endpoint API endpoint
     * @param string $method HTTP method
     * @param array $data Request data
     * @return array Response from PayMongo
     */
    private function sendRequest($endpoint, $method = 'POST', $data = []) {
        
        $ch = curl_init();
        
        // Reduce timeout to 15 seconds to avoid long waits
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 15, // Reduced from 30 seconds to 15 seconds
            CURLOPT_CONNECTTIMEOUT => 5, // Add connection timeout of 5 seconds
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode($this->secretKey . ':')
            ]
        ]);

        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        // Attempt request with retry mechanism
        $attempts = 0;
        $maxAttempts = 2;
        $response = null;
        $err = null;
        
        while ($attempts < $maxAttempts) {
            $response = curl_exec($ch);
            $err = curl_error($ch);
            
            if (!$err) {
                break; // Success, exit retry loop
            }
            
            $attempts++;
            if ($attempts < $maxAttempts) {
                // Log retry attempt
                log_error("Retrying PayMongo API request after failure: " . $err, 'payment_error');
                sleep(1); // Wait 1 second before retry
            }
        }
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err) {
            log_error("PayMongo API Error: " . $err, 'payment_error');
            return [
                'error' => true, 
                'message' => $err,
                'http_code' => $httpCode
            ];
        }

        $decodedResponse = json_decode($response, true);
        
        // Check for API errors in the response
        if ($httpCode >= 400 || isset($decodedResponse['errors'])) {
            log_error("PayMongo API returned error response: " . $response, 'payment_error');
        }
        
        return $decodedResponse;
    }

    /**
     * Get Payment Intent Status
     * @param string $paymentIntentId Payment Intent ID
     * @return array Response from PayMongo
     */
    public function getPaymentIntentStatus($paymentIntentId) {
        return $this->sendRequest("/payment_intents/{$paymentIntentId}", 'GET');
    }

    /**
     * Get Source Status
     * @param string $sourceId Source ID
     * @return array Response from PayMongo
     */
    public function getSourceStatus($sourceId) {
        return $this->sendRequest("/sources/{$sourceId}", 'GET');
    }
}
?> 