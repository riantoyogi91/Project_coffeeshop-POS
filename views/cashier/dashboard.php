<?php
require_once '../../config/database.php';

// Proteksi Halaman: Cek apakah user sudah login dan perannya adalah cashier
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'cashier') {
    header("Location: ../../index.php");
    exit;
}

// Ambil data produk dari database
$query = "SELECT * FROM products ORDER BY category ASC";
$products = mysqli_query($conn, $query);

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
            echo "<script>localStorage.removeItem('cashier_pos_cart'); window.location.href='receipt.php?order_id=$order_id';</script>";
        } catch (Exception $e) {
            // Jika ada error, batalkan semua perubahan
            mysqli_rollback($conn);
            echo "<script>alert('Transaksi Gagal! Terjadi kesalahan pada database. Stok mungkin tidak cukup.'); window.location.href='dashboard.php';</script>";
        }
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashier POS - Coffee Shop</title>
    <script src="https://cdn.tailwindcss.com"></script>
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

    <main id="main-content" class="flex-1 flex flex-col p-6 overflow-hidden">
        <header class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Katalog Menu</h1>
                <p class="text-gray-500 text-sm">Kasir: <span class="font-semibold"><?= $_SESSION['username'] ?></span></p>
            </div>
            <div class="flex items-center gap-4">
                <div class="relative w-72">
                    <input type="text" id="search" placeholder="Cari kopi atau makanan..."
                        class="w-full pl-10 pr-4 py-2 rounded-xl border-none shadow-sm focus:ring-2 focus:ring-orange-500 outline-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 absolute left-3 top-2.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <div class="h-10 w-10 bg-orange-200 rounded-full flex items-center justify-center font-bold text-orange-700 text-sm">CS</div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto pr-2 hide-scrollbar">
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <?php while ($row = mysqli_fetch_assoc($products)): ?>
                    <div onclick="addToCart(<?= $row['id'] ?>, '<?= addslashes($row['name']) ?>', <?= $row['price'] ?>, <?= $row['stock'] ?? 0 ?>)"
                        class="bg-white p-4 rounded-3xl shadow-sm hover:shadow-xl transition-all border-2 border-transparent hover:border-orange-500 cursor-pointer group">
                        <div class="h-36 bg-orange-50 rounded-2xl flex items-center justify-center overflow-hidden group-hover:scale-105 transition-transform">
                            <?php $img = !empty($row['image']) ? $row['image'] : 'default.png'; ?>
                            <img src="../../assets/images/products/<?= $img ?>" class="w-full h-full object-cover" alt="<?= $row['name'] ?>">
                        </div>
                        <div class="mt-4">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-orange-600"><?= $row['category'] ?></span>
                            <h3 class="font-bold text-gray-800 truncate"><?= $row['name'] ?></h3>
                            <p class="text-gray-900 font-extrabold mt-1">Rp <?= number_format($row['price'], 0, ',', '.') ?></p>
                            <?php if (isset($row['stock']) && $row['stock'] <= 0): ?>
                                <p class="text-[10px] text-red-500 font-bold mt-1">Habis!</p>
                            <?php elseif (isset($row['stock'])): ?>
                                <p class="text-[10px] text-slate-400 mt-1">Stok: <?= $row['stock'] ?></p>
                            <?php else: ?>
                                <p class="text-[10px] text-slate-400 mt-1">Stok: N/A</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </main>

    <aside id="cart-sidebar" class="hidden flex-1 bg-white shadow-2xl flex flex-col transition-all duration-300">
        <div class="p-6 border-b flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Detail Pesanan</h2>
                <p class="text-gray-500 text-sm">Kasir: <span class="font-semibold"><?= $_SESSION['username'] ?></span></p>
            </div>
            <div class="h-10 w-10 bg-orange-200 rounded-full flex items-center justify-center font-bold text-orange-700 text-sm">CS</div>
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

            <button onclick="checkout()" class="w-full bg-orange-600 hover:bg-orange-700 text-white font-bold py-4 rounded-2xl shadow-lg shadow-orange-200 mt-4 transition-all active:scale-95">
                PROSES TRANSAKSI
            </button>
        </div>
    </aside>

    <script>
        let cart = [];

        // Muat keranjang dari localStorage jika ada
        if (localStorage.getItem('cashier_pos_cart')) {
            cart = JSON.parse(localStorage.getItem('cashier_pos_cart'));
            renderCart();
        }

        // Cek URL parameter untuk membuka keranjang otomatis (dari halaman lain)
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('open_cart')) {
            toggleCart();
            window.history.replaceState({}, document.title, window.location.pathname);
        }

        function updateCartItemQty(id, delta) {
            const item = cart.find(item => item.id === id);
            if (!item) return;

            const newQty = item.qty + delta;

            if (newQty < 1) {
                if (confirm('Hapus item ini dari keranjang?')) removeFromCart(id);
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

        function addToCart(id, name, price, stock) {
            // Validasi stok di sisi client
            const productInCart = cart.find(item => item.id === id);
            const currentQtyInCart = productInCart ? productInCart.qty : 0;

            if (stock <= currentQtyInCart) {
                alert(`Stok untuk "${name}" tidak mencukupi!`);
                return;
            }

            const existingItem = cart.find(item => item.id === id);
            if (existingItem) {
                existingItem.qty += 1;
            } else {
                cart.push({
                    id,
                    name,
                    price,
                    qty: 1,
                    stock
                });
            }
            saveCart();
            renderCart();
        }

        function removeFromCart(id) {
            cart = cart.filter(item => item.id !== id);
            saveCart();
            renderCart();
        }

        function saveCart() {
            localStorage.setItem('cashier_pos_cart', JSON.stringify(cart));
        }

        function renderCart() {
            const cartContainer = document.getElementById('cart-list');
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
                        <button onclick="removeFromCart(${item.id})" class="text-red-400 hover:text-red-600 ml-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        </button>
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
            const btnCart = document.getElementById('btn-cart');
            const btnMenu = document.getElementById('btn-menu');
            const mainContent = document.getElementById('main-content');
            sidebar.classList.toggle('hidden');
            mainContent.classList.toggle('hidden');

            if (sidebar.classList.contains('hidden')) {
                // Cart Closed -> Menu Active
                btnCart.classList.remove('bg-orange-600', 'text-white');
                btnCart.classList.add('text-slate-400', 'hover:bg-slate-800');

                btnMenu.classList.add('bg-orange-600');
                btnMenu.classList.remove('text-slate-400', 'hover:bg-slate-800');
            } else {
                // Cart Open -> Menu Inactive
                btnCart.classList.add('bg-orange-600', 'text-white');
                btnCart.classList.remove('text-slate-400', 'hover:bg-slate-800');

                btnMenu.classList.remove('bg-orange-600');
                btnMenu.classList.add('text-slate-400', 'hover:bg-slate-800');
            }
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

        // Enhance cart button for SPA-like toggle on this page
        const cartButton = document.getElementById('btn-cart');
        if (cartButton) {
            cartButton.addEventListener('click', function(e) {
                // Mencegah link default agar halaman tidak reload
                e.preventDefault();
                // Jalankan fungsi toggle yang sudah ada
                toggleCart();
            });
        }
    </script>
</body>

</html>