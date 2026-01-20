<?php
require_once '../../config/database.php';

// Proteksi Halaman Admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

// Ambil data produk dari database
$query = "SELECT * FROM products ORDER BY category ASC";
$products = mysqli_query($conn, $query);

// Logika View (Dashboard vs POS vs Cart)
$view = $_GET['view'] ?? 'dashboard'; // dashboard, pos, cart

// Data Ringkasan Dashboard
$summary = [
    'revenue' => 0,
    'transactions' => 0,
    'items_sold' => 0,
    'pending' => 0,
    'low_stock' => 0
];
if ($view === 'dashboard') {
    $today = date('Y-m-d');
    // Ringkasan Cepat
    try {
        $summary_query = mysqli_query($conn, "SELECT 
                (SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE DATE(created_at) = '$today' AND status != 'cancelled') as revenue,
                (SELECT COUNT(*) FROM orders WHERE DATE(created_at) = '$today' AND status != 'cancelled') as transactions,
                (SELECT COALESCE(SUM(quantity), 0) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE DATE(o.created_at) = '$today' AND o.status != 'cancelled') as items_sold,
                (SELECT COUNT(*) FROM orders WHERE status = 'pending') as pending,
                (SELECT COUNT(*) FROM products WHERE stock <= 5) as low_stock
            ");
        // Cek jika query berhasil dan ada hasilnya
        if ($summary_query && mysqli_num_rows($summary_query) > 0) {
            $summary_data = mysqli_fetch_assoc($summary_query);
            $summary = array_merge($summary, $summary_data);
        }
    } catch (Exception $e) {
        // Fallback jika query gagal (misal kolom stock tidak ada)
        try {
            $summary_query = mysqli_query($conn, "SELECT 
                (SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE DATE(created_at) = '$today' AND status != 'cancelled') as revenue,
                (SELECT COUNT(*) FROM orders WHERE DATE(created_at) = '$today' AND status != 'cancelled') as transactions,
                (SELECT COALESCE(SUM(quantity), 0) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE DATE(o.created_at) = '$today' AND o.status != 'cancelled') as items_sold,
                (SELECT COUNT(*) FROM orders WHERE status = 'pending') as pending,
                0 as low_stock
            ");
            if ($summary_query && mysqli_num_rows($summary_query) > 0) {
                $summary_data = mysqli_fetch_assoc($summary_query);
                $summary = array_merge($summary, $summary_data);
            }
        } catch (Exception $ex) {
            // Abaikan error, gunakan nilai default 0
        }
    }

    // Pesanan Terbaru
    $recent_orders = mysqli_query($conn, "SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5");

    // Produk Terlaris
    $best_sellers = mysqli_query($conn, "SELECT p.name, COALESCE(SUM(oi.quantity), 0) as total_sold FROM order_items oi JOIN products p ON oi.product_id = p.id JOIN orders o ON oi.order_id = o.id WHERE o.status != 'cancelled' GROUP BY p.id ORDER BY total_sold DESC LIMIT 5");

    // Data Grafik Penjualan (7 Hari Terakhir)
    $chart_labels = [];
    $chart_data = [];
    $seven_days_ago = date('Y-m-d', strtotime('-6 days'));
    
    $q_chart = mysqli_query($conn, "SELECT DATE(created_at) as date, SUM(total_price) as total FROM orders WHERE status != 'cancelled' AND DATE(created_at) >= '$seven_days_ago' GROUP BY DATE(created_at)");
    
    $sales_data = [];
    if ($q_chart) {
        while($row = mysqli_fetch_assoc($q_chart)) $sales_data[$row['date']] = $row['total'];
    }
    
    for ($i = 6; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $chart_labels[] = date('d M', strtotime($d));
        $chart_data[] = $sales_data[$d] ?? 0;
    }
}

// Handle Checkout (Proses Transaksi)
if (isset($_POST['process_order'])) {
    $cart_data = json_decode($_POST['cart_data'], true);

    if (!empty($cart_data)) {
        // Mulai transaksi untuk menjaga integritas data
        mysqli_begin_transaction($conn);

        try {
            $total_price = 0;
            foreach ($cart_data as $item) {
                $total_price += $item['price'] * $item['qty'];
            }

            // Hitung Pajak & Total
            $tax = $total_price * 0.1;
            $final_total = $total_price + $tax;
            $user_id = $_SESSION['user_id'];

            // 1. Simpan ke tabel orders
            $stmt = $conn->prepare("INSERT INTO orders (user_id, total_price, status) VALUES (?, ?, 'pending')");
            $stmt->bind_param("id", $user_id, $final_total);
            $stmt->execute();
            $order_id = $stmt->insert_id;

            // 2. Simpan item & kurangi stok
            $stmt_items = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt_stock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");

            foreach ($cart_data as $item) {
                $stmt_items->bind_param("iiid", $order_id, $item['id'], $item['qty'], $item['price']);
                $stmt_items->execute();
                $stmt_stock->bind_param("ii", $item['qty'], $item['id']);
                $stmt_stock->execute();
            }

            // Jika semua query berhasil, commit transaksi
            mysqli_commit($conn);
            echo "<script>alert('Transaksi Berhasil! Order ID: #$order_id'); localStorage.removeItem('pos_cart'); window.location.href='dashboard.php';</script>";
        } catch (Exception $e) {
            // Jika ada error, batalkan semua perubahan
            mysqli_rollback($conn);
            echo "<script>alert('Transaksi Gagal! Terjadi kesalahan pada database. Stok mungkin tidak cukup.'); window.location.href='dashboard.php?view=cart';</script>";
        }
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Admin Panel - Coffee Shop</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
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

    <?php if ($view === 'dashboard'): ?>
        <main class="flex-1 p-8 overflow-y-auto">
            <header class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">Dashboard Ringkasan</h1>
                    <p class="text-slate-500">Statistik penjualan hari ini: <?= date('d F Y') ?></p>
                </div>
                <div class="h-10 w-10 bg-orange-200 rounded-full flex items-center justify-center font-bold text-orange-700 text-sm">AD</div>
            </header>

            <!-- Ringkasan Cepat -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                    <p class="text-slate-500 text-xs font-bold uppercase mb-2">Total Penjualan</p>
                    <h3 class="text-2xl font-bold text-slate-800">Rp <?= number_format($summary['revenue'], 0, ',', '.') ?></h3>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                    <p class="text-slate-500 text-xs font-bold uppercase mb-2">Transaksi</p>
                    <h3 class="text-2xl font-bold text-slate-800"><?= $summary['transactions'] ?></h3>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                    <p class="text-slate-500 text-xs font-bold uppercase mb-2">Produk Terjual</p>
                    <h3 class="text-2xl font-bold text-slate-800"><?= $summary['items_sold'] ?> Item</h3>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                    <p class="text-slate-500 text-xs font-bold uppercase mb-2">Pesanan Pending</p>
                    <h3 class="text-2xl font-bold text-orange-600"><?= $summary['pending'] ?></h3>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                    <p class="text-slate-500 text-xs font-bold uppercase mb-2">Stok Menipis</p>
                    <h3 class="text-2xl font-bold <?= $summary['low_stock'] > 0 ? 'text-red-600' : 'text-green-600' ?>"><?= $summary['low_stock'] ?> Item</h3>
                </div>
            </div>

            <!-- Grafik Penjualan -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 mb-8">
                <h3 class="font-bold text-slate-800 mb-4">Grafik Pendapatan (7 Hari Terakhir)</h3>
                <div class="h-64 w-full">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Daftar Pesanan Terbaru -->
                <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                    <h3 class="font-bold text-slate-800 mb-4">Pesanan Terbaru</h3>
                    <table class="w-full text-left text-sm">
                        <thead class="text-slate-400 border-b">
                            <tr>
                                <th class="pb-3">ID</th>
                                <th class="pb-3">Kasir</th>
                                <th class="pb-3">Total</th>
                                <th class="pb-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-slate-600">
                            <?php while ($row = mysqli_fetch_assoc($recent_orders)): ?>
                                <tr class="border-b last:border-0">
                                    <td class="py-3 font-bold">#<?= $row['id'] ?></td>
                                    <td class="py-3"><?= $row['username'] ?></td>
                                    <td class="py-3">Rp <?= number_format($row['total_price'], 0, ',', '.') ?></td>
                                    <td class="py-3"><span class="px-2 py-1 rounded text-xs font-bold bg-slate-100"><?= $row['status'] ?></span></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Produk Terlaris -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                    <h3 class="font-bold text-slate-800 mb-4">Produk Terlaris</h3>
                    <ul class="space-y-4">
                        <?php while ($row = mysqli_fetch_assoc($best_sellers)): ?>
                            <li class="flex justify-between items-center">
                                <span class="text-slate-600"><?= $row['name'] ?></span>
                                <span class="font-bold text-slate-800"><?= $row['total_sold'] ?> Terjual</span>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            </div>
        </main>
    <?php else: ?>

        <main id="main-content" class="flex-1 flex flex-col p-6 overflow-hidden">
            <header class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Katalog Menu</h1>
                    <p class="text-gray-500 text-sm">Admin: <span class="font-semibold"><?= $_SESSION['username'] ?></span></p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="relative w-72">
                        <input type="text" id="search" placeholder="Cari kopi atau makanan..."
                            class="w-full pl-10 pr-4 py-2 rounded-xl border-none shadow-sm focus:ring-2 focus:ring-orange-500 outline-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 absolute left-3 top-2.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <button onclick="toggleCart()" class="bg-white p-2 rounded-xl shadow-sm text-slate-500 hover:text-orange-600 transition relative">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span id="cart-badge" class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full hidden">0</span>
                    </button>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto pr-2 hide-scrollbar">
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    <?php while ($row = mysqli_fetch_assoc($products)): ?>
                        <?php $stock = $row['stock'] ?? 999; // Anggap stok tak terbatas jika tidak di-set 
                        ?>
                        <div class="bg-white p-4 rounded-3xl shadow-sm flex flex-col justify-between border-2 <?= $stock > 0 ? 'border-transparent' : 'border-red-200 bg-red-50/50' ?>">
                            <div>
                                <div class="h-36 bg-orange-50 rounded-2xl flex items-center justify-center overflow-hidden mb-4">
                                    <?php $img = !empty($row['image']) ? $row['image'] : 'default.png'; ?>
                                    <img src="../../assets/images/products/<?= $img ?>" class="w-full h-full object-cover" alt="<?= $row['name'] ?>">
                                </div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-orange-600"><?= $row['category'] ?></span>
                                <h3 class="font-bold text-gray-800 truncate"><?= $row['name'] ?></h3>
                                <p class="text-gray-900 font-extrabold mt-1">Rp <?= number_format($row['price'], 0, ',', '.') ?></p>
                            </div>
                            <div class="mt-4">
                                <?php if ($stock <= 0): ?>
                                    <div class="w-full bg-red-500 text-white text-center font-bold py-2 rounded-lg text-sm">
                                        Stok Habis
                                    </div>
                                <?php else: ?>
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-xs text-slate-500">Stok: <span class="font-bold text-slate-700"><?= $row['stock'] ?? '∞' ?></span></span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="flex items-center gap-1 border rounded-lg p-1">
                                            <button onclick="changeQty(<?= $row['id'] ?>, -1)" class="w-7 h-7 rounded text-slate-500 hover:bg-slate-200 font-bold text-lg">-</button>
                                            <input id="qty-<?= $row['id'] ?>" type="number" value="1" min="1" max="<?= $stock ?>" class="w-10 text-center font-bold text-slate-800 border-none focus:ring-0 bg-transparent p-0" style="-moz-appearance: textfield; appearance: textfield;">
                                            <button onclick="changeQty(<?= $row['id'] ?>, 1, <?= $stock ?>)" class="w-7 h-7 rounded text-slate-500 hover:bg-slate-200 font-bold text-lg">+</button>
                                        </div>
                                        <button onclick="addToCart(<?= $row['id'] ?>, '<?= addslashes($row['name']) ?>', <?= $row['price'] ?>, <?= $stock ?>)" class="flex-1 bg-orange-100 hover:bg-orange-200 text-orange-700 font-bold py-2.5 rounded-lg transition text-sm">
                                            +
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </main>

        <aside id="cart-sidebar" class="hidden w-96 bg-white shadow-2xl flex flex-col transition-all duration-300 border-l border-slate-100 z-40">
            <div class="p-6 border-b flex justify-between items-center">
                <h2 class="text-xl font-bold text-gray-800">Keranjang</h2>
                <button onclick="toggleCart()" class="text-slate-400 hover:text-red-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div id="cart-list" class="flex-1 overflow-y-auto p-6 space-y-4 hide-scrollbar">
                <div class="text-center py-10">
                    <p class="text-gray-400">Keranjang masih kosong</p>
                </div>
            </div>

            <div class="p-6 bg-gray-50 space-y-3">
                <div class="flex justify-between text-gray-600">
                    <span>Subtotal</span>
                    <span id="subtotal">Rp 0</span>
                </div>
                <div class="flex justify-between text-gray-600 border-b pb-3">
                    <span>Pajak (10%)</span>
                    <span id="tax">Rp 0</span>
                </div>
                <div class="flex justify-between text-2xl font-black text-orange-900 pt-2">
                    <span>Total</span>
                    <span id="total-price">Rp 0</span>
                </div>

                <button onclick="checkout()" class="w-full bg-orange-600 hover:bg-orange-700 text-white font-bold py-4 rounded-2xl shadow-lg shadow-orange-200 mt-4 transition-all active:scale-95">PROSES TRANSAKSI</button>
            </div>
        </aside>
    <?php endif; ?>

    <script>
        let cart = [];

        // Load cart from localStorage if available
        if (localStorage.getItem('pos_cart')) {
            cart = JSON.parse(localStorage.getItem('pos_cart'));
            renderCart();
        }

        // Inisialisasi Grafik (Hanya di Dashboard View)
        const chartCanvas = document.getElementById('salesChart');
        if (chartCanvas) {
            const ctx = chartCanvas.getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?= json_encode($chart_labels ?? []) ?>,
                    datasets: [{
                        label: 'Pendapatan (Rp)',
                        data: <?= json_encode($chart_data ?? []) ?>,
                        borderColor: '#ea580c', // orange-600
                        backgroundColor: 'rgba(234, 88, 12, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#ea580c',
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, grid: { borderDash: [2, 4] } }, x: { grid: { display: false } } }
                }
            });
        }

        function changeQty(productId, delta, stock) {
            const qtyInput = document.getElementById(`qty-${productId}`);
            let currentQty = parseInt(qtyInput.value);
            let newQty = currentQty + delta;

            if (newQty < 1) {
                newQty = 1;
            }
            if (newQty > stock) {
                newQty = stock;
                alert('Stok tidak mencukupi!');
            }
            qtyInput.value = newQty;
        }

        function addToCart(id, name, price, stock) {
            const qtyInput = document.getElementById(`qty-${id}`);
            const qtyToAdd = parseInt(qtyInput.value);

            if (qtyToAdd <= 0) return;

            // Validasi stok di sisi client
            const productInCart = cart.find(item => item.id === id);
            const currentQtyInCart = productInCart ? productInCart.qty : 0;

            if (stock < (currentQtyInCart + qtyToAdd)) {
                alert(`Stok untuk "${name}" tidak mencukupi! Sisa stok: ${stock}, di keranjang: ${currentQtyInCart}.`);
                return;
            }

            const existingItem = cart.find(item => item.id === id);
            if (existingItem) {
                existingItem.qty += qtyToAdd;
            } else {
                cart.push({
                    id,
                    name,
                    price,
                    qty: qtyToAdd,
                    stock
                });
            }
            saveCart();
            alert(`${qtyToAdd}x "${name}" berhasil ditambahkan ke keranjang.`);
            renderCart();
            qtyInput.value = 1; // Reset kuantitas ke 1 setelah ditambahkan
        }

        function removeFromCart(id) {
            cart = cart.filter(item => item.id !== id);
            saveCart();
            renderCart();
        }

        function saveCart() {
            localStorage.setItem('pos_cart', JSON.stringify(cart));
        }

        function updateCartItemQty(id, delta) {
            const item = cart.find(item => item.id === id);
            if (!item) return;

            const newQty = item.qty + delta;

            if (newQty < 1) {
                if (confirm('Hapus item ini dari keranjang?')) {
                    removeFromCart(id);
                }
                return;
            }

            if (newQty > item.stock) {
                alert(`Stok tidak mencukupi! Stok tersedia: ${item.stock}`);
                return;
            }

            item.qty = newQty;
            saveCart();
            renderCart();
        }

        function renderCart() {
            const cartContainer = document.getElementById('cart-list');
            const badge = document.getElementById('cart-badge');

            if (cart.length > 0) {
                badge.innerText = cart.reduce((acc, item) => acc + item.qty, 0);
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }

            if (cart.length === 0) {
                cartContainer.innerHTML = '<div class="text-center py-10 text-gray-400">Keranjang masih kosong</div>';
                updateTotals(0);
                return;
            }

            cartContainer.innerHTML = cart.map(item => `
                <div class="flex items-center justify-between bg-white p-3 rounded-2xl border shadow-sm">
                    <div class="flex-1">
                        <h4 class="font-bold text-sm text-gray-800">${item.name}</h4>
                        <p class="text-xs text-orange-600">Rp ${item.price.toLocaleString('id-ID')}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button onclick="updateCartItemQty(${item.id}, -1)" class="w-6 h-6 rounded bg-slate-100 text-slate-600 hover:bg-slate-200 font-bold flex items-center justify-center text-xs">-</button>
                        <span class="text-sm font-bold w-6 text-center">${item.qty}</span>
                        <button onclick="updateCartItemQty(${item.id}, 1)" class="w-6 h-6 rounded bg-slate-100 text-slate-600 hover:bg-slate-200 font-bold flex items-center justify-center text-xs">+</button>
                    </div>
                </div>
            `).join('');

            const subtotal = cart.reduce((acc, item) => acc + (item.price * item.qty), 0);
            updateTotals(subtotal);
        }

        function updateTotals(subtotal) {
            const tax = subtotal * 0.1;
            const total = subtotal + tax;
            document.getElementById('subtotal').innerText = 'Rp ' + subtotal.toLocaleString('id-ID');
            document.getElementById('tax').innerText = 'Rp ' + tax.toLocaleString('id-ID');
            document.getElementById('total-price').innerText = 'Rp ' + total.toLocaleString('id-ID');
        }

        function toggleCart() {
            const sidebar = document.getElementById('cart-sidebar');
            sidebar.classList.toggle('hidden');
        }

        function checkout() {
            if (cart.length === 0) return alert("Keranjang kosong!");
            if (!confirm("Proses transaksi ini?")) return;

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';

            const inputCart = document.createElement('input');
            inputCart.type = 'hidden';
            inputCart.name = 'cart_data';
            inputCart.value = JSON.stringify(cart);

            const inputProcess = document.createElement('input');
            inputProcess.type = 'hidden';
            inputProcess.name = 'process_order';
            inputProcess.value = 'true';

            form.appendChild(inputCart);
            form.appendChild(inputProcess);
            document.body.appendChild(form);
            form.submit();
        }

        // Fungsi Pencarian Produk
        const searchInput = document.getElementById('search');
        if (searchInput) {
            searchInput.addEventListener('keyup', function() {
                const searchTerm = this.value.toLowerCase();
                const productCards = document.querySelectorAll('.grid > div[onclick^="addToCart"]');

                productCards.forEach(card => {
                    const productName = card.querySelector('h3').textContent.toLowerCase();
                    card.style.display = productName.includes(searchTerm) ? 'block' : 'none';
                });
            });
        }
    </script>
</body>

</html>