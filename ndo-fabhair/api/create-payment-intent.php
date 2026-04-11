<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Get Stripe secret key from environment variable (more secure)
// Set this in your server's environment variables or .htaccess SetEnv
$stripeSecretKey = getenv('STRIPE_SECRET_KEY') ?: '';

// Fallback: Try to read from config.php if environment variable not set
if (empty($stripeSecretKey) && file_exists(__DIR__ . '/config.php')) {
    // Note: You should add STRIPE_SECRET_KEY to your config.php or use environment variables
    // For security, never commit the actual key to version control
    require_once __DIR__ . '/config.php';
    $stripeSecretKey = defined('STRIPE_SECRET_KEY') ? STRIPE_SECRET_KEY : '';
}

// Validate that we have a Stripe key
if (empty($stripeSecretKey)) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Stripe configuration error',
        'details' => 'Stripe secret key not configured. Please set STRIPE_SECRET_KEY environment variable.'
    ]);
    exit;
}

// Get request body
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

$amount = $input['amount'] ?? 0;
$currency = $input['currency'] ?? 'gbp';

// Validate amount (minimum £0.50)
if ($amount < 0.50) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid amount. Minimum £0.50']);
    exit;
}

// Convert to pence for Stripe
$amountInPence = round($amount * 100);

// Create payment intent data
$data = [
    'amount' => $amountInPence,
    'currency' => $currency,
];

// Initialize cURL
$ch = curl_init('https://api.stripe.com/v1/payment_intents');

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $stripeSecretKey,
    'Content-Type: application/x-www-form-urlencoded',
]);

// Execute the request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Handle cURL errors
if ($curlError) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to connect to Stripe',
        'details' => $curlError
    ]);
    exit;
}

// Handle HTTP errors
if ($httpCode !== 200) {
    http_response_code(500);
    $errorData = json_decode($response, true);
    echo json_encode([
        'error' => 'Stripe API error',
        'details' => $errorData['error']['message'] ?? 'Unknown error',
        'http_code' => $httpCode
    ]);
    exit;
}

// Parse successful response
$paymentIntent = json_decode($response, true);

if (!$paymentIntent || !isset($paymentIntent['client_secret'])) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Invalid response from Stripe',
        'details' => 'Missing client_secret'
    ]);
    exit;
}

// Return success response
echo json_encode([
    'clientSecret' => $paymentIntent['client_secret'],
    'paymentIntentId' => $paymentIntent['id']
]);
?>