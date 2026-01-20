<?php
require_once '../../config/database.php';

// Proteksi API: Hanya role 'kitchen' yang bisa akses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'kitchen') {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Akses ditolak']);
    exit;
}

// Ambil data pesanan yang relevan untuk dapur (pending & processing)
$query_orders = "SELECT orders.*, users.username as cashier_name 
                 FROM orders 
                 JOIN users ON orders.user_id = users.id 
                 WHERE orders.status IN ('pending', 'processing') 
                 ORDER BY orders.created_at ASC";
$orders_result = mysqli_query($conn, $query_orders);

$orders_data = [];
while ($order = mysqli_fetch_assoc($orders_result)) {
    $order_id = $order['id'];
    $items_query = mysqli_query($conn, "SELECT oi.quantity, p.name, p.image FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = $order_id");
    
    $order['items'] = mysqli_fetch_all($items_query, MYSQLI_ASSOC);
    $orders_data[] = $order;
}

// Kembalikan data dalam format JSON
header('Content-Type: application/json');
echo json_encode($orders_data);
?>