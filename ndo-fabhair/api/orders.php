<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['REQUEST_URI'];

// Handle different endpoints
if ($method === 'GET' && strpos($path, '/orders') !== false) {
    // Get all orders
    try {
        $sql = "SELECT * FROM orders ORDER BY created_at DESC";
        $stmt = $pdo->query($sql);
        $orders = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'orders' => $orders]);
    } catch (Exception $e) {
        http_response_code(500);
        error_log('Orders fetch error: ' . $e->getMessage());
        echo json_encode(['error' => 'Failed to fetch orders']);
    }
    
} elseif ($method === 'POST' && strpos($path, '/orders') !== false) {
    // Create new order
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON input']);
        exit;
    }
    
    try {
        $orderId = 'ORD-' . date('Ymd') . '-' . substr(uniqid(), -6);
        
        $sql = "INSERT INTO orders (order_id, chair_number, customer_name, customer_email, items, total_amount, status, payment_status, stripe_payment_intent_id, notes, delivery_option) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $orderId,
            $input['chair_number'] ?? '',
            $input['customer_name'] ?? '',
            $input['customer_email'] ?? '',
            json_encode($input['items'] ?? []),
            $input['total_amount'] ?? 0,
            $input['status'] ?? 'pending',
            $input['payment_status'] ?? 'pending',
            $input['stripe_payment_intent_id'] ?? '',
            $input['notes'] ?? '',
            $input['delivery_option'] ?? 'Deliver to chair'
        ]);
        
        echo json_encode(['success' => true, 'order_id' => $orderId]);
    } catch (Exception $e) {
        http_response_code(500);
        error_log('Order creation error: ' . $e->getMessage());
        echo json_encode(['error' => 'Failed to create order']);
    }
    
} elseif ($method === 'PUT' && strpos($path, '/orders') !== false) {
    // Update order status
    require_admin();
    
    $input = json_decode(file_get_contents('php://input'), true);
    $orderId = $input['order_id'] ?? '';
    $status = $input['status'] ?? '';
    
    if (!$orderId || !$status) {
        http_response_code(400);
        echo json_encode(['error' => 'Order ID and status are required']);
        exit;
    }
    
    try {
        $sql = "UPDATE orders SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE order_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$status, $orderId]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Order updated successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Order not found']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        error_log('Order update error: ' . $e->getMessage());
        echo json_encode(['error' => 'Failed to update order']);
    }
    
} elseif ($method === 'DELETE' && strpos($path, '/orders') !== false) {
    // Delete order
    require_admin();
    
    $input = json_decode(file_get_contents('php://input'), true);
    $orderId = $input['order_id'] ?? '';
    
    if (!$orderId) {
        http_response_code(400);
        echo json_encode(['error' => 'Order ID is required']);
        exit;
    }
    
    try {
        $sql = "DELETE FROM orders WHERE order_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$orderId]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Order deleted successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Order not found']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        error_log('Order deletion error: ' . $e->getMessage());
        echo json_encode(['error' => 'Failed to delete order']);
    }
    
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Endpoint not found']);
}
?>
