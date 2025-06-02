<?php

/**
 * Onopay Payment Gateway Integration for Indian Businesses (INR Transactions)
 * 
 * This implementation includes all necessary features for Indian payment processing:
 * - INR as primary currency
 * - UPI, NetBanking, Credit/Debit Cards, Wallets support
 * - Mandatory GST fields for Indian businesses
 * - Compliance with RBI guidelines
 * - Support for Indian payment methods
 */

// Configuration Constants for Indian Businesses
define('ONOPAY_MERCHANT_ID', 'YOUR_ONOPAY_MERCHANT_ID');
define('ONOPAY_API_KEY', 'YOUR_ONOPAY_API_KEY');
define('ONOPAY_SALT_KEY_REQUEST', 'YOUR_SALT_FOR_REQUESTS');
define('ONOPAY_SALT_KEY_RESPONSE', 'YOUR_SALT_FOR_RESPONSES');
define('ONOPAY_PAYMENT_INITIATE_URL', 'https://api.onopay.in/payment/initiate');
define('ONOPAY_PAYMENT_STATUS_URL', 'https://api.onopay.in/payment/status');
define('ONOPAY_REFUND_INITIATE_URL', 'https://api.onopay.in/payment/refund');
define('ONOPAY_UPI_COLLECT_URL', 'https://api.onopay.in/upi/collect');
define('ONOPAY_WEBHOOK_URL', 'https://api.onopay.in/webhook');
define('ONOPAY_MANDATE_URL', 'https://api.onopay.in/mandate/create');
define('ONOPAY_CURRENCY', 'INR'); // Fixed to INR for Indian transactions
define('ONOPAY_GST_ENABLED', true);
define('ONOPAY_GST_NUMBER', 'YOUR_GST_NUMBER');
define('ONOPAY_PAN_NUMBER', 'YOUR_PAN_NUMBER');
define('ONOPAY_TDS_PERCENTAGE', 0); // TDS percentage if applicable
define('ONOPAY_DEBUG_MODE', true);
define('ONOPAY_LOG_FILE', __DIR__ . '/onopay_integration.log');
define('ONOPAY_MAX_RETRIES', 3);
define('ONOPAY_RETRY_DELAY', 2); // in seconds
define('ONOPAY_TIMEOUT', 30); // in seconds
define('ONOPAY_SUPPORTED_PAYMENT_METHODS', ['upi', 'netbanking', 'card', 'wallet', 'nb', 'credit_card', 'debit_card']);
define('ONOPAY_AUTO_CAPTURE', true);
define('ONOPAY_ENABLE_WEBHOOKS', true);
define('ONOPAY_WEBHOOK_SECRET', 'your_webhook_secret');
define('ONOPAY_COMPANY_NAME', 'Your Business Name');
define('ONOPAY_COMPANY_ADDRESS', 'Your Business Address in India');
define('ONOPAY_COMPANY_PINCODE', '400001');
define('ONOPAY_COMPANY_STATE', 'Maharashtra');
define('ONOPAY_COMPANY_STATE_CODE', '27');

// Custom Exceptions
class OnopayPaymentException extends \Exception {}
class OnopayConfigurationException extends OnopayPaymentException {}
class OnopayRequestException extends OnopayPaymentException {}
class OnopayResponseException extends OnopayPaymentException {}
class OnopaySecurityException extends OnopayPaymentException {}
class OnopayUPIException extends OnopayPaymentException {}
class OnopayMandateException extends OnopayPaymentException {}
class OnopayNetworkException extends OnopayPaymentException {}

class OnopayIndiaGateway {
    private $merchantId;
    private $apiKey;
    private $saltKeyRequest;
    private $saltKeyResponse;
    private $paymentInitiateUrl;
    private $paymentStatusUrl;
    private $refundInitiateUrl;
    private $upiCollectUrl;
    private $mandateUrl;
    private $debugMode;
    private $gstEnabled;
    private $gstNumber;
    private $panNumber;
    private $tdsPercentage;
    
    private $defaultCurlOptions = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ];

    public function __construct() {
        $this->validateIndianConfiguration();
        
        $this->merchantId = ONOPAY_MERCHANT_ID;
        $this->apiKey = ONOPAY_API_KEY;
        $this->saltKeyRequest = ONOPAY_SALT_KEY_REQUEST;
        $this->saltKeyResponse = ONOPAY_SALT_KEY_RESPONSE;
        $this->paymentInitiateUrl = ONOPAY_PAYMENT_INITIATE_URL;
        $this->paymentStatusUrl = ONOPAY_PAYMENT_STATUS_URL;
        $this->refundInitiateUrl = ONOPAY_REFUND_INITIATE_URL;
        $this->upiCollectUrl = ONOPAY_UPI_COLLECT_URL;
        $this->mandateUrl = ONOPAY_MANDATE_URL;
        $this->debugMode = ONOPAY_DEBUG_MODE;
        $this->gstEnabled = ONOPAY_GST_ENABLED;
        $this->gstNumber = ONOPAY_GST_NUMBER;
        $this->panNumber = ONOPAY_PAN_NUMBER;
        $this->tdsPercentage = ONOPAY_TDS_PERCENTAGE;
    }

    private function validateIndianConfiguration() {
        if (!defined('ONOPAY_GST_NUMBER') && ONOPAY_GST_ENABLED) {
            throw new OnopayConfigurationException("GST number is required for Indian businesses");
        }
        
        if (!defined('ONOPAY_PAN_NUMBER')) {
            throw new OnopayConfigurationException("PAN number is required for Indian businesses");
        }
        
        if (ONOPAY_CURRENCY !== 'INR') {
            throw new OnopayConfigurationException("Currency must be INR for Indian transactions");
        }
    }

    /**
     * Initiate payment for Indian customers
     * 
     * @param string $orderId Unique order ID
     * @param float $amount Amount in INR
     * @param string $customerName Customer name
     * @param string $customerEmail Customer email
     * @param string $customerPhone Customer phone (10 digits)
     * @param string $redirectUrl Callback URL
     * @param string $paymentMethod Preferred payment method (upi, netbanking, card, wallet)
     * @param string $description Payment description
     * @param array $additionalParams Additional parameters
     * @return void
     */
    public function initiateIndianPayment(
        string $orderId,
        float $amount,
        string $customerName,
        string $customerEmail,
        string $customerPhone,
        string $redirectUrl,
        string $paymentMethod = 'upi',
        string $description = 'Payment for Order',
        array $additionalParams = []
    ): void {
        // Validate Indian-specific parameters
        $this->validateIndianPaymentParameters($orderId, $amount, $customerPhone);
        
        $requestData = [
            'merchant_id' => $this->merchantId,
            'api_key' => $this->apiKey,
            'order_id' => $orderId,
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => 'INR',
            'customer_name' => $customerName,
            'customer_email' => $customerEmail,
            'customer_phone' => $customerPhone,
            'redirect_url' => $redirectUrl,
            'description' => $description,
            'payment_method' => $paymentMethod,
            'country' => 'IN',
            'gst_enabled' => $this->gstEnabled ? '1' : '0',
            'gst_number' => $this->gstNumber,
            'pan_number' => $this->panNumber,
        ];

        // Add UPI-specific parameters if payment method is UPI
        if ($paymentMethod === 'upi') {
            $requestData['upi_flow'] = 'collect'; // or 'intent'
            $requestData['upi_expiry'] = '10'; // minutes
        }

        $requestData = array_merge($requestData, $additionalParams);
        $requestData['checksum'] = $this->generateIndianChecksum($requestData);

        $this->renderPaymentForm($requestData);
    }

    private function validateIndianPaymentParameters(
        string $orderId, 
        float $amount, 
        string $customerPhone
    ): void {
        if (empty($orderId)) {
            throw new OnopayRequestException("Order ID cannot be empty");
        }

        if ($amount <= 0) {
            throw new OnopayRequestException("Amount must be greater than zero");
        }

        if (!preg_match('/^[0-9]{10}$/', $customerPhone)) {
            throw new OnopayRequestException("Indian phone number must be 10 digits");
        }

        if ($amount > 200000 && empty($this->panNumber)) {
            throw new OnopayRequestException("PAN number is required for transactions above 2 lakh INR");
        }
    }

    private function generateIndianChecksum(array $data): string {
        $fieldsToHash = [
            'merchant_id', 'api_key', 'order_id', 'amount', 'currency',
            'customer_email', 'customer_phone', 'payment_method', 'gst_number'
        ];
        
        $checksumValues = [];
        foreach ($fieldsToHash as $key) {
            if (isset($data[$key])) {
                $checksumValues[] = $data[$key];
            }
        }
        
        $checksumString = implode('|', $checksumValues);
        $finalStringToHash = $checksumString . '|' . $this->saltKeyRequest;
        return hash('sha512', $finalStringToHash);
    }

    /**
     * Handle payment response from Onopay (Indian version)
     * 
     * @param array $responseData Response data from Onopay
     * @return array Processed response
     */
    public function handleIndianPaymentResponse(array $responseData): array {
        if (empty($responseData)) {
            throw new OnopayResponseException("Received empty response from payment gateway");
        }

        // Verify checksum first for security
        $this->verifyIndianResponseChecksum($responseData);

        $orderId = $responseData['order_id'] ?? null;
        $transactionId = $responseData['transaction_id'] ?? null;
        $statusCode = $responseData['status_code'] ?? null;
        $responseMessage = $responseData['message'] ?? 'No message from gateway';
        $paymentMethod = $responseData['payment_method'] ?? null;
        $upiReference = $responseData['upi_reference_id'] ?? null;
        $bankReference = $responseData['bank_reference_number'] ?? null;

        $status = $this->interpretIndianStatusCode($statusCode);

        return [
            'status' => $status,
            'message' => $responseMessage,
            'order_id' => $orderId,
            'transaction_id' => $transactionId,
            'payment_method' => $paymentMethod,
            'upi_reference' => $upiReference,
            'bank_reference' => $bankReference,
            'raw_response' => $responseData,
            'gst_amount' => $responseData['gst_amount'] ?? 0,
            'tds_amount' => $responseData['tds_amount'] ?? 0,
            'invoice_number' => $responseData['invoice_number'] ?? null,
        ];
    }

    private function interpretIndianStatusCode(?string $statusCode): string {
        if (in_array($statusCode, ['00', 'SUCCESS', 'TXN_SUCCESS'])) {
            return 'success';
        } elseif (in_array($statusCode, ['01', 'FAILURE', 'TXN_FAILURE'])) {
            return 'failed';
        } elseif (in_array($statusCode, ['02', 'PENDING', 'TXN_PENDING'])) {
            return 'pending';
        } elseif ($statusCode === 'UPI_PENDING') {
            return 'upi_pending';
        } else {
            return 'unknown';
        }
    }

    /**
     * Initiate UPI payment
     * 
     * @param string $orderId Unique order ID
     * @param float $amount Amount in INR
     * @param string $customerPhone Customer phone (10 digits)
     * @param string $redirectUrl Callback URL
     * @param string $description Payment description
     * @param string $upiFlow 'collect' or 'intent'
     * @return array UPI payment response
     */
    public function initiateUPIPayment(
        string $orderId,
        float $amount,
        string $customerPhone,
        string $redirectUrl,
        string $description = 'UPI Payment',
        string $upiFlow = 'collect'
    ): array {
        $this->validateUPIParameters($orderId, $amount, $customerPhone);

        $requestData = [
            'merchant_id' => $this->merchantId,
            'api_key' => $this->apiKey,
            'order_id' => $orderId,
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => 'INR',
            'customer_phone' => $customerPhone,
            'redirect_url' => $redirectUrl,
            'description' => $description,
            'payment_method' => 'upi',
            'upi_flow' => $upiFlow,
            'upi_expiry' => '10', // minutes
        ];

        $requestData['checksum'] = $this->generateIndianChecksum($requestData);

        $response = $this->executeCurlRequest($this->upiCollectUrl, $requestData);
        $responseData = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new OnopayResponseException("Invalid JSON response from UPI API");
        }

        if (!isset($responseData['status']) || $responseData['status'] !== 'success') {
            throw new OnopayUPIException($responseData['message'] ?? 'UPI payment initiation failed');
        }

        return $responseData;
    }

    private function validateUPIParameters(
        string $orderId, 
        float $amount, 
        string $customerPhone
    ): void {
        if (empty($orderId)) {
            throw new OnopayRequestException("Order ID cannot be empty for UPI payment");
        }

        if ($amount <= 0) {
            throw new OnopayRequestException("Amount must be greater than zero for UPI payment");
        }

        if (!preg_match('/^[0-9]{10}$/', $customerPhone)) {
            throw new OnopayRequestException("Indian phone number must be 10 digits for UPI payment");
        }

        if ($amount > 100000) {
            throw new OnopayRequestException("UPI payments are limited to 1 lakh INR per transaction");
        }
    }

    /**
     * Create a payment mandate for recurring payments (NACH, UPI Autopay)
     * 
     * @param string $orderId Unique mandate ID
     * @param float $amount Amount in INR
     * @param string $customerName Customer name
     * @param string $customerEmail Customer email
     * @param string $customerPhone Customer phone (10 digits)
     * @param string $bankAccount Bank account number
     * @param string $ifsc IFSC code
     * @param string $mandateType 'nach' or 'upi_autopay'
     * @param string $frequency 'MONTHLY', 'QUARTERLY', etc.
     * @param string $startDate Mandate start date (YYYY-MM-DD)
     * @param string $endDate Mandate end date (YYYY-MM-DD)
     * @return array Mandate creation response
     */
    public function createPaymentMandate(
        string $orderId,
        float $amount,
        string $customerName,
        string $customerEmail,
        string $customerPhone,
        string $bankAccount,
        string $ifsc,
        string $mandateType = 'nach',
        string $frequency = 'MONTHLY',
        string $startDate,
        string $endDate
    ): array {
        $this->validateMandateParameters(
            $orderId, 
            $amount, 
            $customerPhone, 
            $bankAccount, 
            $ifsc,
            $mandateType
        );

        $requestData = [
            'merchant_id' => $this->merchantId,
            'api_key' => $this->apiKey,
            'order_id' => $orderId,
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => 'INR',
            'customer_name' => $customerName,
            'customer_email' => $customerEmail,
            'customer_phone' => $customerPhone,
            'bank_account' => $bankAccount,
            'ifsc_code' => $ifsc,
            'mandate_type' => $mandateType,
            'frequency' => $frequency,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'gst_enabled' => $this->gstEnabled ? '1' : '0',
            'gst_number' => $this->gstNumber,
        ];

        $requestData['checksum'] = $this->generateIndianChecksum($requestData);

        $response = $this->executeCurlRequest($this->mandateUrl, $requestData);
        $responseData = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new OnopayResponseException("Invalid JSON response from Mandate API");
        }

        if (!isset($responseData['status']) || $responseData['status'] !== 'success') {
            throw new OnopayMandateException($responseData['message'] ?? 'Mandate creation failed');
        }

        return $responseData;
    }

    private function validateMandateParameters(
        string $orderId,
        float $amount,
        string $customerPhone,
        string $bankAccount,
        string $ifsc,
        string $mandateType
    ): void {
        if (empty($orderId)) {
            throw new OnopayRequestException("Mandate ID cannot be empty");
        }

        if ($amount <= 0) {
            throw new OnopayRequestException("Amount must be greater than zero for mandate");
        }

        if (!preg_match('/^[0-9]{10}$/', $customerPhone)) {
            throw new OnopayRequestException("Indian phone number must be 10 digits for mandate");
        }

        if (!preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/', $ifsc)) {
            throw new OnopayRequestException("Invalid IFSC code format");
        }

        if (!in_array($mandateType, ['nach', 'upi_autopay'])) {
            throw new OnopayRequestException("Invalid mandate type. Must be 'nach' or 'upi_autopay'");
        }
    }

    /**
     * Execute a cURL request with retry logic
     */
    private function executeCurlRequest(
        string $url,
        array $postData = [],
        string $method = 'POST',
        array $headers = [],
        int $retry = 0
    ): string {
        $ch = curl_init();
        $curlOptions = $this->defaultCurlOptions;
        $curlOptions[CURLOPT_URL] = $url;
        
        if (strtoupper($method) === 'POST') {
            $curlOptions[CURLOPT_POST] = true;
            $curlOptions[CURLOPT_POSTFIELDS] = http_build_query($postData);
        }
        
        if (!empty($headers)) {
            $curlOptions[CURLOPT_HTTPHEADER] = $headers;
        }
        
        curl_setopt_array($ch, $curlOptions);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error || $httpCode >= 500) {
            if ($retry < ONOPAY_MAX_RETRIES) {
                sleep(ONOPAY_RETRY_DELAY);
                return $this->executeCurlRequest($url, $postData, $method, $headers, $retry + 1);
            }
            throw new OnopayNetworkException("cURL error after retries: " . $error);
        }

        return $response;
    }

    /**
     * Verify Indian response checksum
     */
    private function verifyIndianResponseChecksum(array $responseData): void {
        if (empty($responseData['checksum'])) {
            throw new OnopaySecurityException("Checksum missing in response");
        }

        $receivedChecksum = $responseData['checksum'];
        unset($responseData['checksum']);

        $calculatedChecksum = $this->generateIndianChecksum($responseData);

        if (!hash_equals($receivedChecksum, $calculatedChecksum)) {
            throw new OnopaySecurityException("Checksum verification failed. Possible tampering.");
        }
    }

    /**
     * Render payment form for redirection
     */
    private function renderPaymentForm(array $requestData): void {
        echo '<!DOCTYPE html>
        <html lang="en-IN">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Redirecting to Onopay Payment Gateway</title>
            <style>
                body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
                .loader { border: 5px solid #f3f3f3; border-top: 5px solid #3498db; border-radius: 50%; 
                         width: 50px; height: 50px; animation: spin 1s linear infinite; margin: 20px auto; }
                @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
            </style>
        </head>
        <body>
            <div class="loader"></div>
            <h2>Redirecting to Secure Payment Gateway</h2>
            <p>Please wait while we redirect you to Onopay for secure payment processing.</p>
            <p>Do not refresh or press the back button.</p>
            
            <form action="' . htmlspecialchars($this->paymentInitiateUrl) . '" method="POST" name="onopayPaymentForm">
        ';

        foreach ($requestData as $key => $value) {
            echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
        }

        echo '
            </form>
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    setTimeout(function() {
                        document.onopayPaymentForm.submit();
                    }, 1000);
                });
            </script>
        </body>
        </html>';
        exit;
    }

    /**
     * Generate an Indian-style order ID
     */
    public function generateIndianOrderId(string $prefix = 'IN'): string {
        $timestamp = date('YmdHis');
        $random = mt_rand(1000, 9999);
        return $prefix . $timestamp . $random;
    }

    /**
     * Calculate GST amount for a transaction
     */
    public function calculateGstAmount(float $amount, float $gstRate = 18): float {
        return round(($amount * $gstRate) / (100 + $gstRate), 2);
    }

    /**
     * Log messages for debugging
     */
    private function log(string $message, string $level = 'INFO'): void {
        if (!$this->debugMode && $level === 'DEBUG') return;
        
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
        file_put_contents(ONOPAY_LOG_FILE, $logMessage, FILE_APPEND);
    }
}