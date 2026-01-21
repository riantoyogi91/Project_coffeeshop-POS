<?php
require_once '../../config/database.php';

// Proteksi: Hanya role kitchen dan admin yang boleh akses
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['kitchen', 'admin'])) {
    http_response_code(403);
    exit;
}

// Ambil produk dengan stok menipis (<= 5)
// Tambahkan 'id' ke dalam SELECT untuk fitur Quick Update
$query = "SELECT id, name, stock FROM products WHERE stock <= 5 ORDER BY stock ASC";
$result = mysqli_query($conn, $query);

$alerts = [];
while ($row = mysqli_fetch_assoc($result)) {
    $alerts[] = $row;
}

header('Content-Type: application/json');
echo json_encode($alerts);
