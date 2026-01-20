<?php
require_once 'config/database.php';

$message = '';
$status = '';

if (isset($_POST['reset_password'])) {
    $username = $_POST['username'];
    $new_password = $_POST['new_password'];
    $recovery_code = $_POST['recovery_code'];

    // Kode rahasia sederhana untuk reset password (bisa diubah)
    // Dalam skenario nyata, ini bisa diganti dengan verifikasi email/OTP
    $SECRET_CODE = 'admin123';

    if (strlen($new_password) < 6) {
        $message = "Password baru minimal 6 karakter!";
        $status = "error";
    } elseif ($recovery_code === $SECRET_CODE) {
        // Cek apakah username ada
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Hash password baru dan update ke database
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
            $stmt->bind_param("ss", $hashed_password, $username);
            if ($stmt->execute()) {
                $message = "Password berhasil diubah! Silakan login kembali.";
                $status = "success";
            } else {
                $message = "Terjadi kesalahan sistem saat mengupdate password.";
                $status = "error";
            }
        } else {
            $message = "Username tidak ditemukan dalam sistem!";
            $status = "error";
        }
    } else {
        $message = "Kode pemulihan salah! Silakan hubungi Admin.";
        $status = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Coffee Shop</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="flex items-center justify-center h-screen bg-cover bg-center relative" style="background-image: url('https://images.unsplash.com/photo-1497935586351-b67a49e012bf?q=80&w=1920&auto=format&fit=crop');">
    <!-- Overlay Gelap -->
    <div class="absolute inset-0 bg-black/50"></div>

    <div class="bg-white p-8 rounded-2xl shadow-xl w-96 border border-slate-200 relative z-10">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-slate-800">Reset Password</h1>
            <p class="text-slate-500 text-sm">Masukkan data untuk memulihkan akun</p>
        </div>

        <?php if ($message): ?>
            <div class="<?= $status === 'success' ? 'bg-green-50 text-green-600 border-green-100' : 'bg-red-50 text-red-500 border-red-100' ?> p-3 rounded-lg mb-6 text-sm text-center border font-medium">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Username</label>
                <input type="text" name="username" class="w-full border border-slate-300 p-3 rounded-xl focus:ring-2 focus:ring-orange-500 outline-none" placeholder="Username Anda" required>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Password Baru</label>
                <input type="password" name="new_password" minlength="6" class="w-full border border-slate-300 p-3 rounded-xl focus:ring-2 focus:ring-orange-500 outline-none" placeholder="Password baru (min. 6 karakter)" required>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Kode Pemulihan</label>
                <input type="password" name="recovery_code" class="w-full border border-slate-300 p-3 rounded-xl focus:ring-2 focus:ring-orange-500 outline-none" placeholder="Kode dari Admin" required>
                <p class="text-[10px] text-slate-400 mt-1">*Default: admin123</p>
            </div>
            <button type="submit" name="reset_password" class="w-full bg-orange-600 hover:bg-orange-700 text-white font-bold py-3 rounded-xl transition shadow-lg shadow-orange-200 mt-2">Reset Password</button>
            <div class="text-center mt-4">
                <a href="index.php" class="text-sm text-slate-500 hover:text-orange-600 font-semibold">Kembali ke Login</a>
            </div>
        </form>
    </div>
</body>

</html>