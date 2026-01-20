<?php
require_once '../../config/database.php';

// Proteksi Halaman Kitchen
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'kitchen') {
    header("Location: ../../index.php");
    exit;
}

// Ambil Data Riwayat Pesanan (Status: completed)
$query = "SELECT orders.*, users.username as cashier_name 
          FROM orders 
          JOIN users ON orders.user_id = users.id 
          WHERE orders.status = 'completed' 
          ORDER BY orders.created_at DESC";
$history = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Riwayat Pesanan - Kitchen</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }
    </style>
</head>

<body class="bg-gray-100 h-screen flex overflow-hidden">

    <?php include 'sidebar.php'; ?>

    <main class="flex-1 flex flex-col p-6 overflow-hidden">
        <header class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Riwayat Masakan</h1>
                <p class="text-gray-500 text-sm">Daftar pesanan yang telah selesai dibuat.</p>
            </div>
            <div class="h-10 w-10 bg-orange-200 rounded-full flex items-center justify-center font-bold text-orange-700 text-sm">KC</div>
        </header>

        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden flex-1 overflow-y-auto hide-scrollbar">
            <table class="w-full text-left border-collapse">
                <thead class="sticky top-0 bg-white shadow-sm z-10">
                    <tr class="bg-slate-50 text-slate-400 text-[11px] uppercase tracking-widest">
                        <th class="p-4 font-semibold">ID Order</th>
                        <th class="p-4 font-semibold">Waktu Selesai</th>
                        <th class="p-4 font-semibold">Menu Item</th>
                        <th class="p-4 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="text-slate-600 text-sm">
                    <?php if (mysqli_num_rows($history) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($history)): ?>
                            <?php
                            // Ambil item pesanan untuk ditampilkan
                            $order_id = $row['id'];
                            $items_query = mysqli_query($conn, "SELECT p.name, oi.quantity FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = $order_id");
                            ?>
                            <tr class="border-b border-slate-50 hover:bg-slate-50 transition">
                                <td class="p-4 font-bold text-slate-700">#<?= $row['id'] ?></td>
                                <td class="p-4 text-slate-500"><?= date('d M Y H:i', strtotime($row['created_at'])) ?></td>
                                <td class="p-4">
                                    <div class="flex flex-col gap-1">
                                        <?php while ($item = mysqli_fetch_assoc($items_query)): ?>
                                            <span class="text-xs font-medium text-slate-700">• <?= $item['quantity'] ?>x <?= $item['name'] ?></span>
                                        <?php endwhile; ?>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <span class="bg-green-100 text-green-600 px-3 py-1 rounded-full text-xs font-bold uppercase">Selesai</span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="p-8 text-center text-slate-400">Belum ada riwayat pesanan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>

</html>