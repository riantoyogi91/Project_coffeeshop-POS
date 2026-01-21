<?php
require_once 'config/database.php';

$error = '';

// Redirect jika sudah login
if (isset($_SESSION['username'])) {
    header("Location: views/" . $_SESSION['role'] . "/dashboard.php");
    exit;
}

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);

        // Verifikasi password hash
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Catat Log Login
            try {
                $uid = $user['id'];
                // Gunakan try-catch agar login tetap jalan meski log gagal
                mysqli_query($conn, "INSERT INTO activity_logs (user_id, action) VALUES ($uid, 'login')");
            } catch (Exception $e) {
                // Abaikan error log demi kelancaran login
            }

            // Redirect berdasarkan role
            if ($user['role'] === 'admin') {
                header("Location: views/admin/dashboard.php");
            } elseif ($user['role'] === 'cashier') {
                header("Location: views/cashier/dashboard.php");
            } elseif ($user['role'] === 'kitchen') {
                header("Location: views/kitchen/dashboard.php");
            }
            exit;
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Username tidak terdaftar!";
    }
}

// Ambil logo toko dari pengaturan
$store_logo = null;
if (isset($conn)) {
    try {
        $q_logo = mysqli_query($conn, "SELECT logo FROM settings LIMIT 1");
        if ($q_logo && mysqli_num_rows($q_logo) > 0) {
            $store_logo = mysqli_fetch_assoc($q_logo)['logo'];
        }
    } catch (Exception $e) { /* Abaikan */
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Coffee Shop</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="flex items-center justify-center h-screen bg-cover bg-center relative" style="background-image: url('https://images.unsplash.com/photo-1497935586351-b67a49e012bf?q=80&w=1920&auto=format&fit=crop');">
    <!-- Overlay Gelap Transparan -->
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>

    <div class="bg-white/90 backdrop-blur-md p-8 rounded-3xl shadow-2xl w-96 border border-white/20 relative z-10 transform transition-all hover:scale-[1.01]">
        <div class="text-center mb-8">
            <?php if (!empty($store_logo) && file_exists('assets/images/' . $store_logo)): ?>
                <img src="assets/images/<?= $store_logo ?>" alt="Logo" class="w-20 h-20 object-contain mx-auto mb-4 bg-white rounded-full p-2 shadow-md">
            <?php else: ?>
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-orange-100 text-3xl mb-4 shadow-inner">☕</div>
            <?php endif; ?>
            <h1 class="text-3xl font-bold text-slate-800 tracking-tight">Selamat Datang</h1>
            <p class="text-slate-500 text-sm mt-2">Mari Mulai Pelayanan Terbaik Anda</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 text-red-600 p-3 rounded-xl mb-6 text-sm text-center border border-red-100 font-semibold shadow-sm flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-5" id="loginForm">
            <input type="hidden" name="login" value="1">
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-orange-500 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <input type="text" name="username" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none transition-all placeholder-slate-400 font-medium text-slate-700" placeholder="Username" required>
            </div>

            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-orange-500 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <input type="password" name="password" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none transition-all placeholder-slate-400 font-medium text-slate-700" placeholder="Password" required>
            </div>
            <div class="flex justify-end">
                <a href="forgot_password.php" class="text-xs text-slate-500 hover:text-orange-600 font-semibold transition-colors">Lupa Password?</a>
            </div>
            <button type="submit" id="loginBtn" class="w-full bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-bold py-3.5 rounded-xl transition-all shadow-lg shadow-orange-500/30 transform hover:-translate-y-0.5 active:translate-y-0 flex justify-center items-center gap-2">
                Masuk Sekarang
            </button>
        </form>

        <div class="mt-8 text-center">
            <p class="text-xs text-slate-400">© <?= date('Y') ?> Coffee Shop System</p>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('loginBtn');
            // Ubah teks dan tambahkan spinner loading
            btn.innerHTML = '<svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Memproses...';
            // Disable tombol secara visual dan fungsional
            btn.style.pointerEvents = 'none';
            btn.style.opacity = '0.75';
        });
    </script>
</body>

</html>