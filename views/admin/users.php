<?php
require_once '../../config/database.php';

// Proteksi Halaman Admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

// Handle Tambah User
if (isset($_POST['add_user'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Cek username kembar
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        echo "<script>alert('Username sudah digunakan!'); window.location.href='users.php';</script>";
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $avatar = 'default_user.png';

    $stmt = $conn->prepare("INSERT INTO users (username, password, role, avatar) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $hashed_password, $role, $avatar);
    if ($stmt->execute()) {
        header("Location: users.php");
        exit;
    } else {
        echo "<script>alert('Gagal menambah user.');</script>";
    }
}

// Handle Update User
if (isset($_POST['update_user'])) {
    $id = (int)$_POST['id'];
    $role = $_POST['role'];

    $update_parts = [
        "role = '$role'"
    ];

    // Cek jika password baru diisi
    if (!empty($_POST['password'])) {
        $password = $_POST['password'];
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $update_parts[] = "password = '$hashed_password'";
    }

    $query_string = "UPDATE users SET " . implode(', ', $update_parts) . " WHERE id = ?";
    $stmt = $conn->prepare($query_string);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: users.php");
    exit;
}

// Handle Hapus User
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Cegah hapus diri sendiri
    if ($id == $_SESSION['user_id']) {
        echo "<script>alert('Anda tidak bisa menghapus akun sendiri!'); window.location.href='users.php';</script>";
        exit;
    }
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: users.php");
    exit;
}

// Ambil data untuk edit
$edit_data = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    if ($result = $stmt->get_result()) {
        $edit_data = $result->fetch_assoc();
    }
}

// Ambil Data Users
$users = mysqli_query($conn, "SELECT * FROM users ORDER BY role ASC, username ASC");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Manajemen User - Admin Coffee Shop</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
    </style>
</head>

<body class="bg-slate-50 flex">

    <?php include 'sidebar.php'; ?>

    <main class="flex-1 p-8">
        <header class="flex justify-between items-center mb-10">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Manajemen Pengguna</h1>
                <p class="text-slate-500">Kelola akun Kasir, Dapur, dan Admin.</p>
            </div>
            <div class="h-10 w-10 bg-orange-200 rounded-full flex items-center justify-center font-bold text-orange-700 text-sm">AD</div>
        </header>

        <!-- Form Tambah User -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 mb-8">
            <h3 class="font-bold text-slate-700 mb-4"><?= $edit_data ? 'Edit Pengguna' : 'Tambah Pengguna Baru' ?></h3>
            <form method="POST" class="flex flex-wrap gap-4 items-end" autocomplete="off">
                <?php if ($edit_data): ?>
                    <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
                <?php endif; ?>
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs font-bold text-slate-500 mb-1">Username</label>
                    <input type="text" name="username" value="<?= $edit_data['username'] ?? '' ?>" class="w-full border border-slate-200 p-2 rounded-lg focus:ring-2 focus:ring-orange-500 outline-none <?= $edit_data ? 'bg-slate-100 cursor-not-allowed' : '' ?>" <?= $edit_data ? 'readonly' : 'required' ?>>
                    <?php if ($edit_data): ?><p class="text-[10px] text-slate-400 mt-1">Username tidak dapat diubah.</p><?php endif; ?>
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs font-bold text-slate-500 mb-1">Password</label>
                    <input type="password" name="password" placeholder="<?= $edit_data ? 'Kosongkan jika tidak diubah' : '' ?>" class="w-full border border-slate-200 p-2 rounded-lg focus:ring-2 focus:ring-orange-500 outline-none" <?= $edit_data ? '' : 'required' ?>>
                </div>
                <div class="w-48">
                    <label class="block text-xs font-bold text-slate-500 mb-1">Role / Jabatan</label>
                    <select name="role" class="w-full border border-slate-200 p-2 rounded-lg focus:ring-2 focus:ring-orange-500 outline-none">
                        <?php $role = $edit_data['role'] ?? 'cashier'; ?>
                        <option value="cashier" <?= $role == 'cashier' ? 'selected' : '' ?>>Cashier (Kasir)</option>
                        <option value="kitchen" <?= $role == 'kitchen' ? 'selected' : '' ?>>Kitchen (Dapur)</option>
                        <option value="admin" <?= $role == 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>
                <button type="submit" name="<?= $edit_data ? 'update_user' : 'add_user' ?>" class="bg-orange-600 hover:bg-orange-700 text-white font-bold py-2 px-6 rounded-lg transition">
                    <?= $edit_data ? 'Update' : 'Tambah' ?>
                </button>
                <?php if ($edit_data): ?>
                    <a href="users.php" class="bg-slate-200 hover:bg-slate-300 text-slate-600 font-bold py-2 px-4 rounded-lg transition">Batal</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Tabel User -->
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-400 text-[11px] uppercase tracking-widest">
                        <th class="p-4 font-semibold">Avatar</th>
                        <th class="p-4 font-semibold">Username</th>
                        <th class="p-4 font-semibold">Role</th>
                        <th class="p-4 font-semibold">Terdaftar</th>
                        <th class="p-4 font-semibold text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-slate-600 text-sm">
                    <?php while ($row = mysqli_fetch_assoc($users)): ?>
                        <tr class="border-b border-slate-50 hover:bg-slate-50 transition">
                            <td class="p-4">
                                <?php
                                $avatar = !empty($row['avatar']) ? $row['avatar'] : 'default_user.png';
                                $avatar_url = "../../assets/images/avatars/" . $avatar;
                                if (!file_exists(__DIR__ . "/../../assets/images/avatars/" . $avatar)) {
                                    $avatar_url = "https://ui-avatars.com/api/?name=" . urlencode($row['username']) . "&background=random";
                                }
                                ?>
                                <img src="<?= $avatar_url ?>" class="w-10 h-10 rounded-full object-cover border border-slate-200">
                            </td>
                            <td class="p-4 font-bold text-slate-700">
                                <?= $row['username'] ?>
                                <?php if ($row['id'] == $_SESSION['user_id']): ?>
                                    <span class="ml-2 text-[10px] bg-green-100 text-green-600 px-2 py-0.5 rounded-full">Saya</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4">
                                <?php
                                $roleColor = match ($row['role']) {
                                    'admin' => 'bg-purple-100 text-purple-600',
                                    'cashier' => 'bg-blue-100 text-blue-600',
                                    'kitchen' => 'bg-orange-100 text-orange-600',
                                    default => 'bg-slate-100'
                                };
                                ?>
                                <span class="<?= $roleColor ?> px-3 py-1 rounded-full text-xs font-bold uppercase"><?= $row['role'] ?></span>
                            </td>
                            <td class="p-4 text-slate-400 text-xs"><?= isset($row['created_at']) ? date('d M Y', strtotime($row['created_at'])) : '-' ?></td>
                            <td class="p-4 text-center">
                                <?php if ($row['id'] != $_SESSION['user_id']): ?>
                                    <a href="?edit=<?= $row['id'] ?>" class="text-blue-500 hover:text-blue-700 mr-2 text-xs font-bold border border-blue-100 bg-blue-50 px-3 py-1 rounded-lg">Edit</a>
                                    <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Yakin ingin menghapus user ini?')" class="text-red-500 hover:text-red-700 font-bold text-xs border border-red-100 bg-red-50 px-3 py-1 rounded-lg transition">Hapus</a>
                                <?php else: ?>
                                    <span class="text-slate-300 text-xs">Locked</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>

</html>