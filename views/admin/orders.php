<?php
require_once '../../config/database.php';

// Proteksi Halaman Admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

// Handle Cancel Order
if (isset($_POST['cancel_order'])) {
    $id = (int)$_POST['order_id'];
    $stmt = $conn->prepare("UPDATE orders SET status='cancelled' WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: orders.php");
    exit;
}

// Ambil Data Pesanan (Join dengan tabel users untuk nama kasir)
$query = "SELECT orders.*, users.username as cashier_name 
          FROM orders 
          JOIN users ON orders.user_id = users.id 
          ORDER BY orders.created_at DESC";
$orders = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Data Pesanan - Admin Coffee Shop</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
    </style>
</head>

<body class="bg-slate-50 flex">

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <main class="flex-1 p-8">
        <header class="flex justify-between items-center mb-10">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Riwayat Pesanan</h1>
                <p class="text-slate-500">Daftar transaksi yang masuk ke sistem.</p>
            </div>
            <div class="h-10 w-10 bg-orange-200 rounded-full flex items-center justify-center font-bold text-orange-700 text-sm">AD</div>
        </header>

        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-400 text-[11px] uppercase tracking-widest">
                        <th class="p-4 font-semibold">ID Order</th>
                        <th class="p-4 font-semibold">Pelanggan</th>
                        <th class="p-4 font-semibold">Kasir</th>
                        <th class="p-4 font-semibold">Total Harga</th>
                        <th class="p-4 font-semibold">Metode</th>
                        <th class="p-4 font-semibold">Status</th>
                        <th class="p-4 font-semibold">Waktu</th>
                        <th class="p-4 font-semibold text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-slate-600 text-sm">
                    <?php if (mysqli_num_rows($orders) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($orders)): ?>
                            <tr class="border-b border-slate-50 hover:bg-slate-50 transition">
                                <td class="p-4 font-bold text-slate-700">#<?= $row['id'] ?></td>
                                <td class="p-4 font-semibold text-slate-600"><?= $row['customer_name'] ?? '-' ?></td>
                                <td class="p-4"><?= ucfirst($row['cashier_name']) ?></td>
                                <td class="p-4 font-bold text-orange-600">Rp <?= number_format($row['total_price'], 0, ',', '.') ?></td>
                                <td class="p-4 text-xs font-bold text-slate-500"><?= $row['payment_method'] ?? 'Cash' ?></td>
                                <td class="p-4">
                                    <?php
                                    $statusColor = match ($row['status']) {
                                        'completed' => 'bg-green-100 text-green-600',
                                        'pending' => 'bg-yellow-100 text-yellow-600',
                                        'processing' => 'bg-blue-100 text-blue-600',
                                        'cancelled' => 'bg-red-100 text-red-600',
                                        default => 'bg-slate-100 text-slate-600'
                                    };
                                    ?>
                                    <span class="<?= $statusColor ?> px-3 py-1 rounded-full text-xs font-bold uppercase"><?= $row['status'] ?></span>
                                </td>
                                <td class="p-4 text-slate-400 text-xs"><?= date('d M Y H:i', strtotime($row['created_at'])) ?></td>
                                <td class="p-4 text-center">
                                    <?php if ($row['status'] === 'pending'): ?>
                                        <form method="POST" onsubmit="return confirm('Yakin ingin membatalkan pesanan ini?');">
                                            <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                                            <button type="submit" name="cancel_order" class="text-red-500 hover:text-red-700 font-bold text-xs border border-red-100 bg-red-50 px-3 py-1 rounded-lg transition">Batalkan</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-slate-300">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-400">Belum ada data pesanan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>

</html>