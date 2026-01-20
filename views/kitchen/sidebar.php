<?php
// Ambil logo dari database untuk sidebar
$sidebar_logo = null;
if (isset($conn)) { // $conn should be available from the parent file
    try {
        $q_logo = mysqli_query($conn, "SELECT logo FROM settings LIMIT 1");
        if ($q_logo && mysqli_num_rows($q_logo) > 0) {
            $sidebar_logo = mysqli_fetch_assoc($q_logo)['logo'];
        }
    } catch (Exception $e) { /* Abaikan jika tabel/kolom belum ada */
    }
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="w-64 bg-slate-900 min-h-screen p-6 text-white flex-shrink-0 sticky top-0">
    <div class="flex items-center gap-3 mb-10 px-2">
        <?php if (!empty($sidebar_logo) && file_exists(__DIR__ . '/../../assets/images/' . $sidebar_logo)): ?>
            <img src="../../assets/images/<?= $sidebar_logo ?>" alt="Logo" class="w-10 h-10 object-contain bg-white rounded-full p-1">
        <?php else: ?>
            <div class="bg-orange-500 p-2 rounded-full">👨‍🍳</div>
        <?php endif; ?>
        <span class="text-xl font-bold tracking-tight">KitchenPOS</span>
    </div>
    <nav class="space-y-2">
        <a href="dashboard.php" class="flex items-center gap-3 p-3 rounded-xl transition <?php echo ($current_page == 'dashboard.php') ? 'bg-orange-600' : 'text-slate-400 hover:bg-slate-800'; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
            </svg>
            Pesanan
        </a>
        <a href="history.php" class="flex items-center gap-3 p-3 rounded-xl transition <?php echo ($current_page == 'history.php') ? 'bg-orange-600' : 'text-slate-400 hover:bg-slate-800'; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Riwayat
        </a>
        <a href="../../logout.php" class="flex items-center gap-3 p-3 text-red-400 hover:bg-red-900/20 rounded-xl transition mt-10">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
            </svg>
            Logout
        </a>
    </nav>
</aside>