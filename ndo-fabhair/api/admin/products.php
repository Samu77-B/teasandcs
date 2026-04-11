<?php
require_once __DIR__ . '/../config.php';
require_admin();

$method = $_SERVER['REQUEST_METHOD'];
$raw = file_get_contents('php://input');
$body = json_decode($raw, true) ?: [];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    if ($method === 'GET') {
        $stmt = $pdo->query('SELECT * FROM products ORDER BY id DESC');
        echo json_encode($stmt->fetchAll());
    } elseif ($method === 'POST') {
        if ($id) {
            // Update
            $stmt = $pdo->prepare('UPDATE products SET name=?, category=?, regular_price=?, large_price=?, type=?, description=? WHERE id=?');
            $stmt->execute([
                $body['name'] ?? '',
                $body['category'] ?? '',
                $body['regularPrice'] ?? 0,
                $body['largePrice'] ?? 0,
                $body['type'] ?? '',
                $body['description'] ?? null,
                $id
            ]);
            echo json_encode(['status' => 'updated']);
        } else {
            // Create
            $stmt = $pdo->prepare('INSERT INTO products (name, category, regular_price, large_price, type, description) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $body['name'] ?? '',
                $body['category'] ?? '',
                $body['regularPrice'] ?? 0,
                $body['largePrice'] ?? 0,
                $body['type'] ?? '',
                $body['description'] ?? null
            ]);
            echo json_encode(['status' => 'created', 'id' => $pdo->lastInsertId()]);
        }
    } elseif ($method === 'DELETE') {
        if (!$id) { http_response_code(400); echo json_encode(['error' => 'Missing id']); exit; }
        $stmt = $pdo->prepare('DELETE FROM products WHERE id=?');
        $stmt->execute([$id]);
        echo json_encode(['status' => 'deleted']);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log('Admin products error: ' . $e->getMessage());
    echo json_encode(['error' => 'Server error']);
}
?>


