<?php
// Ambil logo dari database untuk sidebar
$sidebar_logo = null;
if (isset($conn)) {
    try {
        $q_logo = mysqli_query($conn, "SELECT logo FROM settings LIMIT 1");
        if ($q_logo && mysqli_num_rows($q_logo) > 0) {
            $sidebar_logo = mysqli_fetch_assoc($q_logo)['logo'];
        }
    } catch (Exception $e) { /* Abaikan jika tabel belum ada */
    }
}

$current_page = basename($_SERVER['PHP_SELF']);
$view = $_GET['view'] ?? 'dashboard';

function getLinkClass($page, $target_view = null)
{
    global $current_page, $view;
    $active = false;

    if ($current_page === $page) {
        if ($target_view !== null) {
            $active = ($view === $target_view);
        } elseif ($page === 'dashboard.php') {
            $active = ($view === 'dashboard');
        } else {
            $active = true;
        }
    }

    return $active
        ? 'bg-orange-600 text-white shadow-lg shadow-orange-200/20'
        : 'text-slate-400 hover:bg-slate-800 hover:text-white';
}
?>
<aside class="w-64 bg-slate-900 min-h-screen p-6 text-white flex-shrink-0 sticky top-0 z-50">
    <div class="flex items-center gap-3 mb-10 px-2">
        <?php if (!empty($sidebar_logo)): ?>
            <img src="../../assets/images/<?= $sidebar_logo ?>" alt="Logo" class="w-10 h-10 object-contain bg-white rounded-full p-1">
        <?php else: ?>
            <div class="bg-orange-500 p-2 rounded-full">☕</div>
        <?php endif; ?>
        <span class="text-xl font-bold tracking-tight">AdminPOS</span>
    </div>

    <nav class="space-y-2">
        <a href="dashboard.php" class="flex items-center gap-3 p-3 rounded-xl transition <?= getLinkClass('dashboard.php', 'dashboard') ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
            </svg>
            Dashboard
        </a>
        <a href="dashboard.php?view=pos" class="flex items-center gap-3 p-3 rounded-xl transition <?= getLinkClass('dashboard.php', 'pos') ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
            </svg>
            Menu / POS
        </a>
        <a href="products.php" class="flex items-center gap-3 p-3 rounded-xl transition <?= getLinkClass('products.php') ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
            </svg>
            Produk
        </a>
        <a href="orders.php" class="flex items-center gap-3 p-3 rounded-xl transition <?= getLinkClass('orders.php') ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
            </svg>
            Pesanan
        </a>
        <a href="reports.php" class="flex items-center gap-3 p-3 rounded-xl transition <?= getLinkClass('reports.php') ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Laporan
        </a>
        <a href="users.php" class="flex items-center gap-3 p-3 rounded-xl transition <?= getLinkClass('users.php') ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
            </svg>
            Pengguna
        </a>
        <a href="settings.php" class="flex items-center gap-3 p-3 rounded-xl transition <?= getLinkClass('settings.php') ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            Pengaturan
        </a>
        <a href="../../logout.php" class="flex items-center gap-3 p-3 text-red-400 hover:bg-red-900/20 rounded-xl transition mt-10">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
            </svg>
            Logout
        </a>
    </nav>
</aside>