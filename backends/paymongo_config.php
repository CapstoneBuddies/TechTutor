<?php
// PayMongo Configuration
define('PAYMONGO_SECRET_KEY', $_ENV['PayMongo_SECRET']); // Replace with your actual secret key
define('PAYMONGO_PUBLIC_KEY', $_ENV['PayMongo_PUBLIC']); // Replace with your actual public key
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
                        'paymaya'
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
        $data = [
            'data' => [
                'attributes' => $paymentDetails
            ]
        ];

        return $this->sendRequest('/payment_methods', 'POST', $data);
    }

    /**
     * Attach Payment Method to Payment Intent
     * @param string $paymentIntentId Payment Intent ID
     * @param string $paymentMethodId Payment Method ID
     * @return array Response from PayMongo
     */
    public function attachPaymentMethod($paymentIntentId, $paymentMethodId) {
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
     * Send request to PayMongo API
     * @param string $endpoint API endpoint
     * @param string $method HTTP method
     * @param array $data Request data
     * @return array Response from PayMongo
     */
    private function sendRequest($endpoint, $method = 'POST', $data = []) {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
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

        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            return ['error' => true, 'message' => $err];
        }

        return json_decode($response, true);
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
