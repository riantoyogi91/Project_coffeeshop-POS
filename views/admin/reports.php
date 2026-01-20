<?php
require_once '../../config/database.php';

// Proteksi Halaman Admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

// Filter Tanggal
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

$query = "SELECT DATE(created_at) as date, COUNT(*) as total_orders, SUM(total_price) as revenue 
          FROM orders 
          WHERE status != 'cancelled'";

$params = [];
$types = "";

if (!empty($start_date) && !empty($end_date)) {
    $query .= " AND DATE(created_at) BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
    $types .= "ss";
}

$query .= " GROUP BY DATE(created_at) ORDER BY date DESC";
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$reports = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Penjualan - Admin Coffee Shop</title>
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
                <h1 class="text-2xl font-bold text-slate-800">Laporan Penjualan</h1>
                <p class="text-slate-500">Rekapitulasi pendapatan harian.</p>
            </div>
            <div class="h-10 w-10 bg-orange-200 rounded-full flex items-center justify-center font-bold text-orange-700 text-sm">AD</div>
        </header>

        <!-- Filter Tanggal -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 mb-8">
            <form method="GET" class="flex gap-4 items-end">
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Dari Tanggal</label>
                    <input type="date" name="start_date" value="<?= $start_date ?>" class="border border-slate-200 p-2 rounded-lg text-sm outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="<?= $end_date ?>" class="border border-slate-200 p-2 rounded-lg text-sm outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white font-bold py-2 px-6 rounded-lg text-sm transition">Filter</button>
                <?php if (!empty($start_date)): ?>
                    <a href="reports.php" class="bg-slate-200 hover:bg-slate-300 text-slate-600 font-bold py-2 px-4 rounded-lg text-sm transition">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-400 text-[11px] uppercase tracking-widest">
                        <th class="p-4 font-semibold">Tanggal</th>
                        <th class="p-4 font-semibold">Jumlah Transaksi</th>
                        <th class="p-4 font-semibold">Total Pendapatan</th>
                        <th class="p-4 font-semibold">Rata-rata / Transaksi</th>
                    </tr>
                </thead>
                <tbody class="text-slate-600 text-sm">
                    <?php if (mysqli_num_rows($reports) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($reports)): ?>
                            <tr class="border-b border-slate-50 hover:bg-slate-50 transition">
                                <td class="p-4 font-bold text-slate-700"><?= date('d F Y', strtotime($row['date'])) ?></td>
                                <td class="p-4"><?= $row['total_orders'] ?> Pesanan</td>
                                <td class="p-4 font-bold text-green-600">Rp <?= number_format($row['revenue'], 0, ',', '.') ?></td>
                                <td class="p-4 text-slate-500">
                                    <?php $avg = $row['revenue'] / $row['total_orders']; ?>
                                    Rp <?= number_format($avg, 0, ',', '.') ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="p-8 text-center text-slate-400">Belum ada data laporan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>

</html>