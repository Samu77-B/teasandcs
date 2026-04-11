<?php
/**
 * Input Validation and Sanitization Functions
 * Security: Prevent SQL injection, XSS, and data corruption
 */

/**
 * Sanitize string input to prevent XSS
 * @param string $input
 * @return string
 */
function sanitize_string($input) {
    if (!is_string($input)) {
        return '';
    }
    // Remove null bytes
    $input = str_replace(chr(0), '', $input);
    // HTML escape to prevent XSS
    return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Validate and sanitize email address
 * @param string $email
 * @return string|false Returns sanitized email or false if invalid
 */
function validate_email($email) {
    if (!is_string($email) || empty($email)) {
        return false;
    }
    
    // Remove whitespace
    $email = trim($email);
    
    // Validate email format
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    
    // Additional security: check for suspicious patterns
    if (strlen($email) > 254) { // RFC 5321 limit
        return false;
    }
    
    return $email;
}

/**
 * Validate chair number
 * @param mixed $chairNumber
 * @return string|false Returns sanitized chair number or false if invalid
 */
function validate_chair_number($chairNumber) {
    if (empty($chairNumber)) {
        return ''; // Empty is allowed (for takeout orders)
    }
    
    // Convert to string
    $chair = (string) $chairNumber;
    
    // Allow alphanumeric and common characters (1-999, "A1", etc.)
    if (!preg_match('/^[A-Za-z0-9\-\s]{1,10}$/', $chair)) {
        return false;
    }
    
    return sanitize_string($chair);
}

/**
 * Validate customer name
 * @param string $name
 * @return string|false Returns sanitized name or false if invalid
 */
function validate_customer_name($name) {
    if (!is_string($name) || empty(trim($name))) {
        return false;
    }
    
    $name = trim($name);
    
    // Length validation (reasonable limits)
    if (strlen($name) > 100) {
        return false;
    }
    
    // Allow letters, spaces, hyphens, apostrophes (international names)
    if (!preg_match('/^[\p{L}\s\-\']+$/u', $name)) {
        return false;
    }
    
    return sanitize_string($name);
}

/**
 * Validate monetary amount
 * @param mixed $amount
 * @return float|false Returns validated amount or false if invalid
 */
function validate_amount($amount) {
    // Convert to float
    if (!is_numeric($amount)) {
        return false;
    }
    
    $amount = (float) $amount;
    
    // Minimum amount check (e.g., £0.50)
    if ($amount < 0.50) {
        return false;
    }
    
    // Maximum amount check (reasonable limit, e.g., £10,000)
    if ($amount > 10000) {
        return false;
    }
    
    // Round to 2 decimal places
    return round($amount, 2);
}

/**
 * Validate order status
 * @param string $status
 * @return string|false Returns valid status or false
 */
function validate_order_status($status) {
    $validStatuses = ['pending', 'processing', 'ready', 'completed', 'cancelled'];
    
    if (!in_array($status, $validStatuses, true)) {
        return false;
    }
    
    return $status;
}

/**
 * Validate payment status
 * @param string $status
 * @return string|false Returns valid status or false
 */
function validate_payment_status($status) {
    $validStatuses = ['pending', 'processing', 'completed', 'failed', 'refunded'];
    
    if (!in_array($status, $validStatuses, true)) {
        return false;
    }
    
    return $status;
}

/**
 * Sanitize JSON input (for order items, notes, etc.)
 * @param mixed $input
 * @return array|string
 */
function sanitize_json_input($input) {
    if (is_array($input)) {
        $sanitized = [];
        foreach ($input as $key => $value) {
            $sanitized[sanitize_string($key)] = sanitize_json_input($value);
        }
        return $sanitized;
    } elseif (is_string($input)) {
        return sanitize_string($input);
    } elseif (is_numeric($input)) {
        return $input;
    } elseif (is_bool($input)) {
        return $input;
    } else {
        return '';
    }
}

/**
 * Validate order items array
 * @param array $items
 * @return array|false Returns validated items or false
 */
function validate_order_items($items) {
    if (!is_array($items) || empty($items)) {
        return false;
    }
    
    $validatedItems = [];
    
    foreach ($items as $item) {
        if (!is_array($item)) {
            return false;
        }
        
        // Required fields
        if (empty($item['name']) || empty($item['quantity']) || !isset($item['price'])) {
            return false;
        }
        
        // Validate quantity (must be positive integer)
        $quantity = filter_var($item['quantity'], FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 100]
        ]);
        
        if ($quantity === false) {
            return false;
        }
        
        // Validate price
        $price = validate_amount($item['price']);
        if ($price === false) {
            return false;
        }
        
        $validatedItems[] = [
            'name' => sanitize_string($item['name']),
            'quantity' => $quantity,
            'price' => $price,
            'size' => isset($item['size']) ? sanitize_string($item['size']) : null
        ];
    }
    
    return $validatedItems;
}

/**
 * Rate limiting check (simple implementation)
 * Note: For production, use Redis or memcached
 * @param string $identifier (IP address or user ID)
 * @param int $maxRequests
 * @param int $timeWindow Seconds
 * @return bool Returns true if allowed, false if rate limited
 */
function check_rate_limit($identifier, $maxRequests = 100, $timeWindow = 3600) {
    $rateLimitFile = sys_get_temp_dir() . '/rate_limit_' . md5($identifier) . '.json';
    
    if (file_exists($rateLimitFile)) {
        $data = json_decode(file_get_contents($rateLimitFile), true);
        
        // Check if time window has passed
        if (time() - $data['timestamp'] > $timeWindow) {
            // Reset
            unlink($rateLimitFile);
            return true;
        }
        
        // Check if limit exceeded
        if ($data['count'] >= $maxRequests) {
            return false;
        }
        
        // Increment count
        $data['count']++;
    } else {
        // Create new entry
        $data = [
            'timestamp' => time(),
            'count' => 1
        ];
    }
    
    file_put_contents($rateLimitFile, json_encode($data));
    return true;
}

/**
 * Get client IP address for rate limiting
 * @return string
 */
function get_client_ip() {
    $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    
    foreach ($ipKeys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, 
                    FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}
?>
