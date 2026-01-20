<?php
require_once '../../config/database.php';

// Proteksi Halaman Admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

// AUTO-FIX: Cek kelengkapan struktur database (tambahkan kolom jika hilang)
try {
    // Cek kolom stock
    $check_stock = mysqli_query($conn, "SHOW COLUMNS FROM products LIKE 'stock'");
    if ($check_stock && mysqli_num_rows($check_stock) == 0) {
        mysqli_query($conn, "ALTER TABLE products ADD COLUMN stock INT DEFAULT 0");
    }
    // Cek kolom image (jaga-jaga jika hilang juga)
    $check_image = mysqli_query($conn, "SHOW COLUMNS FROM products LIKE 'image'");
    if ($check_image && mysqli_num_rows($check_image) == 0) {
        mysqli_query($conn, "ALTER TABLE products ADD COLUMN image VARCHAR(255) DEFAULT 'default.png'");
    }
} catch (Throwable $e) { /* Silent error */ }

// Handle Update Produk
if (isset($_POST['update_product'])) {
    try {
        $id = (int)$_POST['id'];
        $name = $_POST['name'];
        $category = $_POST['category'];
        $price = (int)$_POST['price'];
        $stock = (int)$_POST['stock'];

        // Handle Upload Gambar (Jika ada)
        $image_sql = "";
        $params = [$name, $category, $price, $stock];
        $types = "ssii";

        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $target_dir = "../../assets/images/products/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed_types = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($ext, $allowed_types)) {
                // Validasi Ukuran File (Max 2MB)
                if ($_FILES['image']['size'] <= 2 * 1024 * 1024) {
                    $new_name = uniqid() . "." . $ext;
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $target_dir . $new_name)) {
                        // Hapus gambar lama jika bukan default
                        $stmt_img = $conn->prepare("SELECT image FROM products WHERE id = ?");
                        $stmt_img->bind_param("i", $id);
                        $stmt_img->execute();
                        $old_img = $stmt_img->get_result()->fetch_assoc()['image'];
                        if ($old_img && $old_img !== 'default.png' && file_exists($target_dir . $old_img)) {
                            unlink($target_dir . $old_img);
                        }
                        $image_sql = ", image=?";
                        $params[] = $new_name;
                        $types .= "s";
                    }
                } else {
                    echo "<script>alert('Gagal: Ukuran file melebihi 2MB!'); window.location.href='products.php';</script>";
                    exit;
                }
            }
        }

        $params[] = $id;
        $types .= "i";
        $stmt = $conn->prepare("UPDATE products SET name=?, category=?, price=?, stock=? $image_sql WHERE id=?");
        if (!$stmt) throw new Exception("Database Error (Prepare): " . $conn->error);
        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) throw new Exception("Database Error (Execute): " . $stmt->error);

        header("Location: products.php");
        exit;
    } catch (Throwable $e) {
        echo "<script>alert('Update Gagal! Terjadi kesalahan: " . addslashes($e->getMessage()) . "'); window.location.href='products.php';</script>";
        exit;
    }
}

// Handle Tambah Produk
if (isset($_POST['add_product'])) {
    try {
        $name = $_POST['name'];
        $category = $_POST['category'];
        $price = (int)$_POST['price'];
        $stock = (int)$_POST['stock'];
        $image = 'default.png'; // Gambar default

        // Handle Upload Gambar
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $target_dir = "../../assets/images/products/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed_types = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($ext, $allowed_types)) {
                // Validasi Ukuran File (Max 2MB)
                if ($_FILES['image']['size'] <= 2 * 1024 * 1024) {
                    $new_name = uniqid() . "." . $ext;
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $target_dir . $new_name)) {
                        $image = $new_name;
                    }
                } else {
                    echo "<script>alert('Gagal: Ukuran file melebihi 2MB!'); window.location.href='products.php';</script>";
                    exit;
                }
            }
        }

        if (!empty($name) && !empty($price)) {
            $stmt = $conn->prepare("INSERT INTO products (name, category, price, stock, image) VALUES (?, ?, ?, ?, ?)");
            if (!$stmt) throw new Exception("Database Error (Prepare): " . $conn->error);
            $stmt->bind_param("ssiis", $name, $category, $price, $stock, $image);
            if (!$stmt->execute()) throw new Exception("Database Error (Execute): " . $stmt->error);
            header("Location: products.php");
            exit;
        } else {
            throw new Exception("Nama produk dan harga wajib diisi!");
        }
    } catch (Throwable $e) {
        echo "<script>alert('Tambah Produk Gagal! Terjadi kesalahan: " . addslashes($e->getMessage()) . "'); window.location.href='products.php';</script>";
        exit;
    }
}

// Handle Hapus Produk
if (isset($_GET['delete'])) {
    try {
        $id = (int)$_GET['delete'];

        // Cek apakah produk sedang digunakan di transaksi (Database Integrity)
        $stmt_check = $conn->prepare("SELECT id FROM order_items WHERE product_id = ? LIMIT 1");
        $stmt_check->bind_param("i", $id);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            echo "<script>alert('Gagal: Produk tidak dapat dihapus karena sudah ada riwayat transaksi! Data harus dijaga untuk laporan.'); window.location.href='products.php';</script>";
            exit;
        }

        // Hapus gambar dari server
        $target_dir = "../../assets/images/products/";
        $stmt_img = $conn->prepare("SELECT image FROM products WHERE id = ?");
        $stmt_img->bind_param("i", $id);
        $stmt_img->execute();
        $row = $stmt_img->get_result()->fetch_assoc();

        if ($row && $row['image'] !== 'default.png' && file_exists($target_dir . $row['image'])) {
            unlink($target_dir . $row['image']);
        }

        $stmt_del = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt_del->bind_param("i", $id);
        $stmt_del->execute();
        header("Location: products.php");
        exit;
    } catch (Throwable $e) {
        echo "<script>alert('Hapus Gagal! Terjadi kesalahan: " . addslashes($e->getMessage()) . "'); window.location.href='products.php';</script>";
        exit;
    }
}

// Handle Reset / Hapus Semua Produk
if (isset($_POST['delete_all_products'])) {
    try {
        // Matikan pemeriksaan foreign key sementara
        mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");

        // Hapus data transaksi (Orders & Order Items) agar produk bisa dihapus
        mysqli_query($conn, "TRUNCATE TABLE order_items");
        mysqli_query($conn, "TRUNCATE TABLE orders");

        // Hapus semua gambar fisik (kecuali default)
        $res = mysqli_query($conn, "SELECT image FROM products");
        while ($row = mysqli_fetch_assoc($res)) {
            if ($row['image'] != 'default.png' && file_exists("../../assets/images/products/" . $row['image'])) {
                unlink("../../assets/images/products/" . $row['image']);
            }
        }

        // Hapus data di database dan reset ID
        mysqli_query($conn, "TRUNCATE TABLE products");

        // Hidupkan kembali pemeriksaan foreign key
        mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");

        echo "<script>alert('Sistem Bersih! Semua produk dan riwayat transaksi berhasil dihapus.'); window.location.href='products.php';</script>";
        exit;
    } catch (Throwable $e) {
        echo "<script>alert('Error: " . addslashes($e->getMessage()) . "'); window.location.href='products.php';</script>";
        exit;
    }
}

// Ambil Data Produk
$products = null;
try {
    $products = mysqli_query($conn, "SELECT * FROM products ORDER BY id DESC");
} catch (Exception $e) {
    // Tampilkan pesan error jika query gagal, ini akan mencegah fatal error dan memberitahu user.
    $db_error = "Tidak dapat mengambil data produk. Pesan: " . $e->getMessage();
}

// Ambil Data untuk Edit
$edit_data = null;
if (isset($_GET['edit'])) {
    try {
        $id = (int)$_GET['edit'];
        $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        if ($result = $stmt->get_result()) {
            $edit_data = $result->fetch_assoc();
        }
    } catch (Exception $e) {
        $db_error = "Tidak dapat mengambil data produk untuk diedit. Pesan: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Manajemen Produk - Admin Coffee Shop</title>
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
                <h1 class="text-2xl font-bold text-slate-800">Daftar Produk</h1>
                <p class="text-slate-500">Kelola menu makanan dan minuman.</p>
            </div>
            <div class="flex items-center gap-4">
                <form method="POST" onsubmit="return confirm('PERINGATAN: Tindakan ini akan menghapus SEMUA PRODUK dan SEMUA RIWAYAT TRANSAKSI. Data tidak dapat dikembalikan! Yakin ingin mereset sistem?');">
                    <button type="submit" name="delete_all_products" class="bg-red-100 hover:bg-red-200 text-red-600 font-bold py-2 px-4 rounded-lg text-xs transition">
                        Reset Total (Produk & Transaksi)
                    </button>
                </form>
                <div class="h-10 w-10 bg-orange-200 rounded-full flex items-center justify-center font-bold text-orange-700 text-sm">AD</div>
            </div>
        </header>

        <!-- Form Tambah Produk Sederhana -->
        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 mb-8">
            <h3 class="font-bold text-slate-700 mb-4"><?= $edit_data ? 'Edit Produk' : 'Tambah Menu Baru' ?></h3>
            <form method="POST" class="flex gap-4 items-end" enctype="multipart/form-data">
                <?php if ($edit_data): ?>
                    <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
                <?php endif; ?>

                <div class="flex-1">
                    <label class="block text-xs font-bold text-slate-500 mb-1">Nama Produk</label>
                    <input type="text" name="name" value="<?= $edit_data['name'] ?? '' ?>" class="w-full border border-slate-200 p-2 rounded-lg focus:ring-2 focus:ring-orange-500 outline-none" placeholder="Contoh: Kopi Susu" required>
                </div>
                <div class="w-48">
                    <label class="block text-xs font-bold text-slate-500 mb-1">Kategori</label>
                    <select name="category" class="w-full border border-slate-200 p-2 rounded-lg focus:ring-2 focus:ring-orange-500 outline-none">
                        <?php $cat = $edit_data['category'] ?? ''; ?>
                        <option value="Coffee" <?= $cat == 'Coffee' ? 'selected' : '' ?>>Coffee</option>
                        <option value="Non-Coffee" <?= $cat == 'Non-Coffee' ? 'selected' : '' ?>>Non-Coffee</option>
                        <option value="Food" <?= $cat == 'Food' ? 'selected' : '' ?>>Food</option>
                        <option value="Snack" <?= $cat == 'Snack' ? 'selected' : '' ?>>Snack</option>
                    </select>
                </div>
                <div class="w-48">
                    <label class="block text-xs font-bold text-slate-500 mb-1">Harga (Rp)</label>
                    <input type="number" name="price" value="<?= $edit_data['price'] ?? '' ?>" class="w-full border border-slate-200 p-2 rounded-lg focus:ring-2 focus:ring-orange-500 outline-none" placeholder="0" required>
                </div>
                <div class="w-32">
                    <label class="block text-xs font-bold text-slate-500 mb-1">Stok</label>
                    <input type="number" name="stock" value="<?= $edit_data['stock'] ?? '0' ?>" class="w-full border border-slate-200 p-2 rounded-lg focus:ring-2 focus:ring-orange-500 outline-none" placeholder="0" required>
                </div>
                <div class="w-48">
                    <label class="block text-xs font-bold text-slate-500 mb-1">Foto</label>
                    <input type="file" name="image" class="w-full text-xs text-slate-500 file:mr-2 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100" accept="image/*">
                </div>
                <button type="submit" name="<?= $edit_data ? 'update_product' : 'add_product' ?>" class="bg-orange-600 hover:bg-orange-700 text-white font-bold py-2 px-6 rounded-lg transition">
                    <?= $edit_data ? 'Update' : 'Simpan' ?>
                </button>
                <?php if ($edit_data): ?>
                    <a href="products.php" class="bg-slate-200 hover:bg-slate-300 text-slate-600 font-bold py-2 px-4 rounded-lg transition">Batal</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (isset($db_error)): ?>
            <div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl relative mb-8' role='alert'>
                <div class="flex">
                    <div class="py-1"><svg class="fill-current h-6 w-6 text-red-500 mr-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                            <path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zM10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16zm-1-5a1 1 0 0 1 1-1h2a1 1 0 1 1 0 2h-2a1 1 0 0 1-1-1zm-1-4a1 1 0 1 1-2 0 1 1 0 0 1 2 0z" />
                        </svg></div>
                    <div>
                        <p class="font-bold">Error Database!</p>
                        <p class="text-sm"><?= $db_error ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Tabel Produk -->
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-400 text-[11px] uppercase tracking-widest">
                        <th class="p-4 font-semibold">ID</th>
                        <th class="p-4 font-semibold">Gambar</th>
                        <th class="p-4 font-semibold">Nama Produk</th>
                        <th class="p-4 font-semibold">Kategori</th>
                        <th class="p-4 font-semibold">Harga</th>
                        <th class="p-4 font-semibold">Stok</th>
                        <th class="p-4 font-semibold text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-slate-600 text-sm">
                    <?php if ($products && mysqli_num_rows($products) > 0): while ($row = mysqli_fetch_assoc($products)): ?>
                            <tr class="border-b border-slate-50 hover:bg-slate-50 transition">
                                <td class="p-4 text-slate-400">#<?= $row['id'] ?></td>
                                <td class="p-4">
                                    <?php $img = isset($row['image']) ? $row['image'] : 'default.png'; ?>
                                    <img src="../../assets/images/products/<?= $img ?>" class="w-10 h-10 rounded-lg object-cover bg-slate-100" alt="img">
                                </td>
                                <td class="p-4 font-semibold text-slate-800"><?= $row['name'] ?></td>
                                <td class="p-4"><span class="bg-slate-100 px-2 py-1 rounded-lg text-[10px]"><?= $row['category'] ?></span></td>
                                <td class="p-4 font-bold text-orange-600">Rp <?= number_format($row['price'], 0, ',', '.') ?></td>
                                <td class="p-4">
                                    <?php if (isset($row['stock']) && $row['stock'] <= 5): ?>
                                        <span class="text-red-600 font-bold text-xs bg-red-100 px-2 py-1 rounded-full">Menipis (<?= $row['stock'] ?>)</span>
                                    <?php elseif (isset($row['stock'])): ?>
                                        <span class="text-slate-600 font-bold text-xs"><?= $row['stock'] ?></span>
                                    <?php else: ?>
                                        <span class="text-slate-400 text-xs">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-center">
                                    <a href="?edit=<?= $row['id'] ?>" class="text-blue-500 hover:text-blue-700 mr-2 text-xs font-bold border border-blue-100 bg-blue-50 px-3 py-1 rounded-lg">Edit</a>
                                    <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Yakin ingin menghapus?')" class="text-red-400 hover:text-red-600 text-xs font-bold border border-red-100 bg-red-50 px-3 py-1 rounded-lg">Hapus</a>
                                </td>
                            </tr>
                        <?php endwhile;
                    else: ?>
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-400">Belum ada data produk atau terjadi error saat memuat.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>

</html>