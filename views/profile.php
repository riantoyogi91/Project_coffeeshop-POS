<?php
require_once '../config/database.php';

// Cek Login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$status = '';

// Handle Update Profile
if (isset($_POST['update_profile'])) {
    $updates = [];
    $types = "";
    $params = [];

    // 1. Password
    if (!empty($_POST['new_password'])) {
        if (strlen($_POST['new_password']) < 6) {
            $message = "Password baru minimal 6 karakter!";
            $status = "error";
        } else {
            $hashed = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $updates[] = "password = ?";
            $types .= "s";
            $params[] = $hashed;
        }
    }

    // 2. Avatar
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === 0) {
        $target_dir = "../assets/images/avatars/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

        $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($ext, $allowed)) {
            if ($_FILES['avatar']['size'] <= 2 * 1024 * 1024) {
                $new_name = "avatar_" . $user_id . "_" . time() . "." . $ext;
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target_dir . $new_name)) {
                    // Hapus avatar lama
                    $q = mysqli_query($conn, "SELECT avatar FROM users WHERE id=$user_id");
                    $old = mysqli_fetch_assoc($q)['avatar'];
                    if ($old && $old !== 'default_user.png' && file_exists($target_dir . $old)) {
                        unlink($target_dir . $old);
                    }
                    $updates[] = "avatar = ?";
                    $types .= "s";
                    $params[] = $new_name;
                }
            } else {
                $message = "Ukuran file maksimal 2MB!";
                $status = "error";
            }
        } else {
            $message = "Format file harus JPG, PNG, atau WEBP!";
            $status = "error";
        }
    }

    // Eksekusi Update
    if (empty($message) && !empty($updates)) {
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
        $types .= "i";
        $params[] = $user_id;
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) {
            $message = "Profil berhasil diperbarui!";
            $status = "success";
        } else {
            $message = "Terjadi kesalahan database.";
            $status = "error";
        }
    } elseif (empty($message) && empty($updates)) {
        $message = "Tidak ada perubahan yang disimpan.";
        $status = "info";
    }
}

// Ambil Data User Terbaru
$query = "SELECT * FROM users WHERE id = $user_id";
$user = mysqli_fetch_assoc(mysqli_query($conn, $query));
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - Coffee Shop</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
    </style>
</head>

<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-3xl rounded-3xl shadow-xl overflow-hidden flex flex-col md:flex-row">

        <!-- Sidebar Profil -->
        <div class="bg-slate-900 text-white p-8 md:w-2/5 flex flex-col items-center justify-center text-center relative">
            <!-- Dekorasi -->
            <div class="absolute top-0 left-0 w-full h-full overflow-hidden opacity-10 pointer-events-none">
                <div class="absolute -top-10 -left-10 w-40 h-40 bg-orange-500 rounded-full blur-3xl"></div>
                <div class="absolute bottom-10 right-10 w-40 h-40 bg-blue-500 rounded-full blur-3xl"></div>
            </div>

            <div class="w-32 h-32 rounded-full border-4 border-orange-500 overflow-hidden mb-4 bg-slate-800 relative z-10 shadow-lg">
                <?php
                $avatar_file = !empty($user['avatar']) ? $user['avatar'] : 'default_user.png';
                $avatar_path = "../assets/images/avatars/" . $avatar_file;

                // Cek apakah file ada, jika tidak pakai UI Avatars (Placeholder)
                if (!file_exists(__DIR__ . "/../assets/images/avatars/" . $avatar_file)) {
                    $avatar_path = "https://ui-avatars.com/api/?name=" . urlencode($user['username']) . "&background=f97316&color=fff&size=256";
                }
                ?>
                <img src="<?= $avatar_path ?>" class="w-full h-full object-cover" alt="Avatar">
            </div>

            <h2 class="text-2xl font-bold relative z-10"><?= ucfirst($user['username']) ?></h2>
            <span class="bg-orange-600 text-xs font-bold px-3 py-1 rounded-full mt-2 uppercase tracking-wider relative z-10"><?= $user['role'] ?></span>

            <a href="<?= $user['role'] ?>/dashboard.php" class="mt-8 text-slate-400 hover:text-white text-sm flex items-center gap-2 transition relative z-10 group">
                <svg class="w-4 h-4 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Kembali ke Dashboard
            </a>
        </div>

        <!-- Form Edit -->
        <div class="p-8 md:w-3/5 bg-white">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-xl font-bold text-slate-800">Edit Profil</h1>
                <div class="bg-orange-100 text-orange-600 p-2 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                    </svg>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="<?= $status === 'success' ? 'bg-green-50 text-green-600 border-green-100' : ($status === 'info' ? 'bg-blue-50 text-blue-600 border-blue-100' : 'bg-red-50 text-red-500 border-red-100') ?> p-3 rounded-xl text-sm mb-6 font-medium border flex items-center gap-2">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="space-y-5">
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Username</label>
                    <input type="text" value="<?= $user['username'] ?>" disabled class="w-full bg-slate-50 border border-slate-200 p-3 rounded-xl text-slate-500 font-medium cursor-not-allowed select-none">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Ganti Foto Profil</label>
                    <div class="relative">
                        <input type="file" name="avatar" accept="image/*" class="w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100 transition border border-slate-200 rounded-xl cursor-pointer">
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1 ml-1">Format: JPG, PNG, WEBP (Max. 2MB)</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">Password Baru</label>
                    <input type="password" name="new_password" placeholder="••••••••" class="w-full border border-slate-200 p-3 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none transition placeholder-slate-300">
                    <p class="text-[10px] text-slate-400 mt-1 ml-1">Kosongkan jika tidak ingin mengubah password.</p>
                </div>

                <div class="pt-4">
                    <button type="submit" name="update_profile" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-3.5 rounded-xl transition shadow-lg shadow-slate-200 flex justify-center items-center gap-2">
                        <span>Simpan Perubahan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>