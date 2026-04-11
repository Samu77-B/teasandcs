<?php
require_once 'config.php';
require_once 'validation.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['REQUEST_URI'];

// Handle different endpoints
if ($method === 'GET' && strpos($path, '/orders') !== false) {
    // Get all orders - Rate limiting
    $clientIp = get_client_ip();
    if (!check_rate_limit($clientIp, 60, 60)) { // 60 requests per minute
        http_response_code(429);
        echo json_encode(['error' => 'Too many requests. Please try again later.']);
        exit;
    }
    
    // Admin only for GET requests (view all orders)
    require_admin();
    
    try {
        $sql = "SELECT * FROM orders ORDER BY created_at DESC";
        $stmt = $pdo->query($sql);
        $orders = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'orders' => $orders]);
    } catch (Exception $e) {
        error_log('Order fetch error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch orders']);
    }
    
} elseif ($method === 'POST' && strpos($path, '/orders') !== false) {
    // Create new order - Rate limiting
    $clientIp = get_client_ip();
    if (!check_rate_limit($clientIp, 10, 60)) { // 10 orders per minute per IP
        http_response_code(429);
        echo json_encode(['error' => 'Too many requests. Please try again later.']);
        exit;
    }
    
    // Get and validate input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON input']);
        exit;
    }
    
    // Validate and sanitize inputs
    $chairNumber = validate_chair_number($input['chair_number'] ?? '');
    if ($chairNumber === false && !empty($input['chair_number'] ?? '')) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid chair number']);
        exit;
    }
    
    $customerName = validate_customer_name($input['customer_name'] ?? '');
    if ($customerName === false) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid customer name']);
        exit;
    }
    
    $customerEmail = validate_email($input['customer_email'] ?? '');
    if ($customerEmail === false) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email address']);
        exit;
    }
    
    $orderItems = validate_order_items($input['items'] ?? []);
    if ($orderItems === false) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid order items']);
        exit;
    }
    
    $totalAmount = validate_amount($input['total_amount'] ?? 0);
    if ($totalAmount === false) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid total amount']);
        exit;
    }
    
    // Validate payment intent ID (alphanumeric only)
    $paymentIntentId = '';
    if (!empty($input['stripe_payment_intent_id'] ?? '')) {
        $pid = $input['stripe_payment_intent_id'];
        if (preg_match('/^pi_[a-zA-Z0-9_]+$/', $pid)) {
            $paymentIntentId = sanitize_string($pid);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid payment intent ID']);
            exit;
        }
    }
    
    // Sanitize notes
    $notes = sanitize_string($input['notes'] ?? '');
    if (strlen($notes) > 500) {
        $notes = substr($notes, 0, 500); // Limit length
    }
    
    try {
        $orderId = 'ORD-' . date('Ymd') . '-' . substr(uniqid(), -6);
        
        $sql = "INSERT INTO orders (order_id, chair_number, customer_name, customer_email, items, total_amount, status, payment_status, stripe_payment_intent_id, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $orderId,
            $chairNumber,
            $customerName,
            $customerEmail,
            json_encode($orderItems),
            $totalAmount,
            'pending',
            $input['payment_status'] ?? 'pending',
            $paymentIntentId,
            $notes
        ]);
        
        echo json_encode(['success' => true, 'order_id' => $orderId]);
    } catch (Exception $e) {
        error_log('Order creation error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create order']);
    }
    
} elseif ($method === 'PUT' && strpos($path, '/orders') !== false) {
    // Update order status - Admin only
    require_admin();
    
    $input = json_decode(file_get_contents('php://input'), true);
    $orderId = $input['order_id'] ?? '';
    $status = $input['status'] ?? '';
    
    // Validate order ID format
    if (empty($orderId) || !preg_match('/^ORD-\d{8}-[a-zA-Z0-9]{6}$/', $orderId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid order ID format']);
        exit;
    }
    
    // Validate status
    $validatedStatus = validate_order_status($status);
    if ($validatedStatus === false) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid order status']);
        exit;
    }
    
    try {
        $sql = "UPDATE orders SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE order_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$validatedStatus, sanitize_string($orderId)]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Order updated successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Order not found']);
        }
    } catch (Exception $e) {
        error_log('Order update error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update order']);
    }
    
} elseif ($method === 'DELETE' && strpos($path, '/orders') !== false) {
    // Delete order - Admin only
    require_admin();
    
    $input = json_decode(file_get_contents('php://input'), true);
    $orderId = $input['order_id'] ?? '';
    
    // Validate order ID format
    if (empty($orderId) || !preg_match('/^ORD-\d{8}-[a-zA-Z0-9]{6}$/', $orderId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid order ID format']);
        exit;
    }
    
    try {
        $sql = "DELETE FROM orders WHERE order_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([sanitize_string($orderId)]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Order deleted successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Order not found']);
        }
    } catch (Exception $e) {
        error_log('Order deletion error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete order']);
    }
    
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Endpoint not found']);
}
?>
