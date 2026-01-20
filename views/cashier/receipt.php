<?php
require_once '../../config/database.php';

// Proteksi Halaman: Cek apakah user sudah login dan perannya adalah cashier
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'cashier') {
    header("Location: ../../index.php");
    exit;
}

// Validasi Order ID dari URL
if (!isset($_GET['order_id'])) {
    header("Location: dashboard.php");
    exit;
}

$order_id = (int)$_GET['order_id'];

// Ambil data pesanan utama
$order_query = mysqli_query($conn, "SELECT o.*, u.username as cashier_name FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = $order_id");
if (mysqli_num_rows($order_query) === 0) {
    die("Error: Pesanan tidak ditemukan.");
}
$order = mysqli_fetch_assoc($order_query);

// Ambil item-item dalam pesanan
$items_query = mysqli_query($conn, "SELECT oi.*, p.name as product_name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = $order_id");

// Hitung subtotal (sebelum pajak) untuk ditampilkan di struk
$subtotal = 0;
mysqli_data_seek($items_query, 0); // Reset pointer query
while ($item = mysqli_fetch_assoc($items_query)) {
    $subtotal += $item['price'] * $item['quantity'];
}
$tax = $order['total_price'] - $subtotal;

// Ambil Pengaturan Toko
$settings_query = mysqli_query($conn, "SELECT * FROM settings LIMIT 1");
$settings = mysqli_fetch_assoc($settings_query);

// Default values jika database kosong
$store_name = $settings['store_name'] ?? 'LAHAULA COFFEE';
$store_address = $settings['store_address'] ?? 'Alamat Toko Belum Diatur';
$store_phone = $settings['store_phone'] ?? '';
$footer_note = $settings['footer_note'] ?? 'Terima Kasih Atas Kunjungan Anda!';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Struk Pesanan #<?= $order_id ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Courier+Prime:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Courier Prime', monospace;
        }

        @media print {
            .no-print {
                display: none;
            }

            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .receipt-container {
                box-shadow: none;
                border: none;
                margin: 0;
                padding: 0;
            }
        }
    </style>
</head>

<body class="bg-slate-200 flex flex-col items-center justify-center min-h-screen p-4">

    <div id="receipt" class="receipt-container w-full max-w-sm bg-white p-6 rounded-lg shadow-xl border">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold uppercase"><?= $store_name ?></h1>
            <p class="text-xs"><?= $store_address ?></p>
            <?php if ($store_phone): ?><p class="text-xs">Telp: <?= $store_phone ?></p><?php endif; ?>
        </div>

        <div class="text-xs border-t border-b border-dashed border-black py-2 mb-4">
            <div class="flex justify-between"><span>Order ID:</span><span>#<?= $order['id'] ?></span></div>
            <div class="flex justify-between"><span>Tanggal:</span><span><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></span></div>
            <div class="flex justify-between"><span>Kasir:</span><span><?= ucfirst($order['cashier_name']) ?></span></div>
        </div>

        <div>
            <?php mysqli_data_seek($items_query, 0); // Reset pointer lagi 
            ?>
            <?php while ($item = mysqli_fetch_assoc($items_query)): ?>
                <div class="mb-2 text-xs">
                    <p class="font-bold"><?= $item['product_name'] ?></p>
                    <div class="flex justify-between">
                        <span><?= $item['quantity'] ?> x <?= number_format($item['price'], 0, ',', '.') ?></span>
                        <span><?= number_format($item['quantity'] * $item['price'], 0, ',', '.') ?></span>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <div class="text-xs border-t border-dashed border-black mt-4 pt-2">
            <div class="flex justify-between"><span>Subtotal</span><span><?= number_format($subtotal, 0, ',', '.') ?></span></div>
            <div class="flex justify-between"><span>Pajak (10%)</span><span><?= number_format($tax, 0, ',', '.') ?></span></div>
            <div class="flex justify-between font-bold text-sm mt-2 pt-2 border-t border-black">
                <span>TOTAL</span>
                <span>Rp <?= number_format($order['total_price'], 0, ',', '.') ?></span>
            </div>
        </div>

        <div class="text-center mt-8 text-xs">
            <p><?= $footer_note ?></p>
        </div>
    </div>

    <div class="no-print mt-8 flex gap-4">
        <a href="dashboard.php" class="bg-slate-600 hover:bg-slate-700 text-white font-bold py-3 px-6 rounded-lg transition">
            Kembali ke POS
        </a>
        <button onclick="window.print()" class="bg-orange-600 hover:bg-orange-700 text-white font-bold py-3 px-6 rounded-lg transition">
            Cetak Struk
        </button>
    </div>

</body>

</html>