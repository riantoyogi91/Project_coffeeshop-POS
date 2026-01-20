<?php
require_once '../../config/database.php';

// Proteksi Halaman Kitchen
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'kitchen') {
    header("Location: ../../index.php");
    exit;
}

// Handle Update Status Pesanan
if (isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['new_status']; // processing, completed, atau cancelled

    // Menggunakan prepared statement untuk keamanan
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $order_id);
    $stmt->execute();
    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Kitchen Display System - Coffee Shop</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .order-card {
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes flash-red-bg {
            50% {
                background-color: #fee2e2;
                /* bg-red-100 */
            }
        }

        .is-late {
            animation: slideIn 0.3s ease-out, flash-red-bg 1.5s infinite;
            border-color: #dc2626 !important;
            /* red-600 */
        }

        .is-late>div:first-child {
            /* Target header card */
            background-color: #dc2626 !important;
        }
    </style>
</head>

<body class="bg-gray-100 h-screen flex overflow-hidden">

    <?php include 'sidebar.php'; ?>

    <main class="flex-1 flex flex-col p-6 overflow-hidden">
        <header class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Daftar Pesanan</h1>
                <p class="text-gray-500 text-sm">Kitchen Display System</p>
            </div>
            <div class="h-10 w-10 bg-orange-200 rounded-full flex items-center justify-center font-bold text-orange-700 text-sm">KC</div>
        </header>

        <div class="flex-1 overflow-y-auto pr-2 hide-scrollbar">
            <div id="orders-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <!-- Kartu pesanan akan dimuat di sini oleh JavaScript -->
                <div id="placeholder-card" class="border-2 border-dashed border-gray-300 rounded-3xl flex flex-col items-center justify-center p-10 text-gray-400">
                    <div class="animate-spin mb-4 text-2xl">⏳</div>
                    <p class="text-sm">Menunggu pesanan baru...</p>
                </div>
            </div>
        </div>
    </main>

    <!-- Audio Notifikasi (Gunakan file lokal atau URL eksternal) -->
    <audio id="notif-sound" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3"></audio>

    <script>
        const ordersContainer = document.getElementById('orders-container');
        const placeholderCard = document.getElementById('placeholder-card');

        function renderOrders(orders) {
            // Bersihkan container sebelum render ulang
            ordersContainer.innerHTML = '';

            if (orders.length > 0) {
                orders.forEach(order => {
                    // --- Logika Keterlambatan Pesanan ---
                    const orderTime = new Date(order.created_at.replace(' ', 'T')); // Fix for cross-browser date parsing
                    const now = new Date();
                    const minutesDiff = (now - orderTime) / (1000 * 60);
                    let lateClass = '';

                    if (minutesDiff > 15) {
                        lateClass = 'is-late';
                    }

                    const isPending = order.status === 'pending';
                    const cardColor = isPending ? 'border-orange-600' : 'border-blue-500';
                    const headerColor = isPending ? 'bg-orange-600' : 'bg-blue-500';
                    const statusLabel = lateClass ? 'TERLAMBAT' : (isPending ? 'Baru!' : 'Diproses');
                    const statusTextClass = isPending ? 'text-orange-600' : 'text-blue-500';

                    let itemsHtml = order.items.map(item => {
                        const img = item.image ? item.image : 'default.png';
                        return `
                        <li class="flex items-center gap-3 border-b border-gray-50 last:border-0 pb-2 last:pb-0">
                            <img src="../../assets/images/products/${img}" class="w-12 h-12 rounded-lg object-cover bg-gray-100 border border-gray-200" alt="img">
                            <span class="font-semibold text-gray-800 text-sm">${item.quantity}x ${item.name}</span>
                        </li>`;
                    }).join('');

                    let actionButtons = '';
                    if (isPending) {
                        actionButtons = `
                            <div class="flex gap-2 mt-4">
                                <form method="POST" class="flex-1">
                                    <input type="hidden" name="order_id" value="${order.id}">
                                    <input type="hidden" name="new_status" value="processing">
                                    <button type="submit" name="update_status" class="w-full bg-orange-100 text-orange-700 hover:bg-orange-200 font-bold py-3 rounded-xl transition">TERIMA</button>
                                </form>
                                <form method="POST" class="flex-1" onsubmit="return confirm('Batalkan pesanan ini?');">
                                    <input type="hidden" name="order_id" value="${order.id}">
                                    <input type="hidden" name="new_status" value="cancelled">
                                    <button type="submit" name="update_status" class="w-full bg-red-100 text-red-700 hover:bg-red-200 font-bold py-3 rounded-xl transition">TOLAK</button>
                                </form>
                            </div>`;
                    } else {
                        actionButtons = `
                            <form method="POST">
                                <input type="hidden" name="order_id" value="${order.id}">
                                <input type="hidden" name="new_status" value="completed">
                                <button type="submit" name="update_status" class="w-full bg-blue-100 text-blue-700 hover:bg-blue-200 font-bold py-3 rounded-xl mt-4 transition">SELESAI DIBUAT</button>
                            </form>`;
                    }

                    const cardHtml = `
                    <div class="order-card bg-white rounded-3xl border-2 ${cardColor} ${lateClass} overflow-hidden shadow-sm">
                        <div class="${headerColor} p-4 flex justify-between items-center">
                            <span class="font-bold text-sm text-white">ORDER #${order.id}</span>
                            <span class="text-xs bg-white ${statusTextClass} px-2 py-1 rounded-full font-bold italic">${statusLabel}</span>
                        </div>
                        <div class="p-5 space-y-4">
                            <div class="flex justify-between text-xs text-gray-400">
                                <span>Kasir: ${order.cashier_name.charAt(0).toUpperCase() + order.cashier_name.slice(1)}</span>
                                <span>${new Date(order.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}</span>
                            </div>
                            <ul class="space-y-3">${itemsHtml}</ul>
                            ${actionButtons}
                        </div>
                    </div>`;
                    ordersContainer.insertAdjacentHTML('beforeend', cardHtml);
                });
            } else {
                ordersContainer.appendChild(placeholderCard);
                placeholderCard.style.display = 'flex';
            }
        }

        let previousPendingIds = [];
        let firstLoad = true;

        async function fetchOrders() {
            try {
                const response = await fetch('api_fetch_orders.php');
                if (!response.ok) return;
                const orders = await response.json();

                // Logika Notifikasi Suara untuk Pesanan Baru
                const currentPending = orders.filter(o => o.status === 'pending');
                const currentPendingIds = currentPending.map(o => o.id);

                if (!firstLoad) {
                    const hasNewOrders = currentPendingIds.some(id => !previousPendingIds.includes(id));
                    if (hasNewOrders) {
                        document.getElementById('notif-sound').play().catch(e => console.log('Audio play blocked:', e));
                    }
                }

                previousPendingIds = currentPendingIds;
                firstLoad = false;

                renderOrders(orders);
            } catch (error) {
                console.error('Error fetching data:', error);
            }
        }

        // Panggil data pertama kali dan set interval refresh 5 detik
        fetchOrders();
        setInterval(fetchOrders, 5000);
    </script>
</body>

</html>