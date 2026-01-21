<?php
require_once '../../config/database.php';

// Proteksi Halaman Cashier
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'cashier') {
    header("Location: ../../index.php");
    exit;
}

// Handle Batalkan Pesanan
if (isset($_POST['cancel_order'])) {
    $id = (int)$_POST['order_id'];
    // Pastikan hanya bisa cancel yang statusnya pending
    $stmt = $conn->prepare("UPDATE orders SET status='cancelled' WHERE id=? AND status='pending'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: pending.php");
    exit;
}

// Ambil Data Pesanan Pending
$query = "SELECT orders.*, users.username as cashier_name 
          FROM orders 
          JOIN users ON orders.user_id = users.id 
          WHERE orders.status = 'pending' 
          ORDER BY orders.created_at DESC";
$orders = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Pesanan Pending - Cashier</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-100 h-screen flex overflow-hidden">

    <?php include 'sidebar.php'; ?>

    <main class="flex-1 p-8 overflow-y-auto">
        <header class="flex justify-between items-center mb-10">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Pesanan Pending</h1>
                <p class="text-slate-500">Kelola pesanan yang belum diproses oleh dapur.</p>
            </div>
            <div class="h-10 w-10 bg-orange-200 rounded-full flex items-center justify-center font-bold text-orange-700 text-sm">CS</div>
        </header>

        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-400 text-[11px] uppercase tracking-widest">
                        <th class="p-4 font-semibold">ID Order</th>
                        <th class="p-4 font-semibold">Pelanggan</th>
                        <th class="p-4 font-semibold">Total Harga</th>
                        <th class="p-4 font-semibold">Waktu</th>
                        <th class="p-4 font-semibold">Detail Item</th>
                        <th class="p-4 font-semibold text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-slate-600 text-sm">
                    <?php if (mysqli_num_rows($orders) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($orders)): ?>
                            <tr class="border-b border-slate-50 hover:bg-slate-50 transition">
                                <td class="p-4 font-bold text-slate-700">#<?= $row['id'] ?></td>
                                <td class="p-4 text-sm font-semibold"><?= $row['customer_name'] ?? 'Umum' ?></td>
                                <td class="p-4 font-bold text-orange-600">Rp <?= number_format($row['total_price'], 0, ',', '.') ?></td>
                                <td class="p-4 text-slate-400 text-xs"><?= date('d M Y H:i', strtotime($row['created_at'])) ?></td>
                                <td class="p-4">
                                    <?php
                                    $oid = $row['id'];
                                    $items = mysqli_query($conn, "SELECT p.name, oi.quantity FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = $oid");
                                    while ($item = mysqli_fetch_assoc($items)) {
                                        echo "<div class='text-xs'>• {$item['quantity']}x {$item['name']}</div>";
                                    }
                                    ?>
                                </td>
                                <td class="p-4 text-center">
                                    <form method="POST" onsubmit="return confirm('Batalkan pesanan ini? Pelanggan tidak jadi beli?');">
                                        <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                                        <button type="submit" name="cancel_order" class="bg-red-100 hover:bg-red-200 text-red-600 font-bold py-2 px-4 rounded-lg text-xs transition">Batalkan</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="p-8 text-center text-slate-400">Tidak ada pesanan pending saat ini.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>

</html>