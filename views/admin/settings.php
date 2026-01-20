<?php
require_once '../../config/database.php';

// Proteksi Halaman Admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

// Handle Update Settings
$message = '';
if (isset($_POST['update_settings'])) {
    $store_name = $_POST['store_name'];
    $store_address = $_POST['store_address'];
    $store_phone = $_POST['store_phone'];
    $footer_note = $_POST['footer_note'];

    // Handle Logo Upload
    $logo_new_name = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
        $target_dir = "../../assets/images/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($ext, $allowed)) {
            if ($_FILES['logo']['size'] <= 2 * 1024 * 1024) {
                $new_name = "logo_" . time() . "." . $ext;

                // Hapus logo lama jika ada
                $q_old = mysqli_query($conn, "SELECT logo FROM settings WHERE id=1");
                if ($q_old) {
                    $old_logo = mysqli_fetch_assoc($q_old)['logo'] ?? null;
                    if ($old_logo && file_exists($target_dir . $old_logo)) {
                        unlink($target_dir . $old_logo);
                    }
                }

                if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_dir . $new_name)) {
                    $logo_new_name = $new_name;
                }
            } else {
                $message = "Gagal: Ukuran logo maksimal 2MB!";
            }
        } else {
            $message = "Gagal: Format logo harus JPG, PNG, atau WEBP!";
        }
    }

    if (empty($message)) {
        // Update data (asumsi ID selalu 1)
        $sql = "UPDATE settings SET store_name=?, store_address=?, store_phone=?, footer_note=?";
        $types = "ssss";
        $params = [$store_name, $store_address, $store_phone, $footer_note];

        if ($logo_new_name) {
            $sql .= ", logo=?";
            $types .= "s";
            $params[] = $logo_new_name;
        }
        $sql .= " WHERE id=1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            $message = "Pengaturan berhasil disimpan!";
        } else {
            $message = "Gagal menyimpan pengaturan.";
        }
    }
}

// Handle Backup Database
if (isset($_POST['backup_database'])) {
    $tables = [];
    $result = mysqli_query($conn, "SHOW TABLES");
    while ($row = mysqli_fetch_row($result)) {
        $tables[] = $row[0];
    }

    $sqlScript = "-- Database Backup: " . $db . "\n";
    $sqlScript .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";
    $sqlScript .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    foreach ($tables as $table) {
        // Structure
        $query = "SHOW CREATE TABLE $table";
        $result = mysqli_query($conn, $query);
        $row = mysqli_fetch_row($result);
        $sqlScript .= "-- Structure for table `$table`\n";
        $sqlScript .= "DROP TABLE IF EXISTS `$table`;\n";
        $sqlScript .= $row[1] . ";\n\n";

        // Data
        $query = "SELECT * FROM $table";
        $result = mysqli_query($conn, $query);
        
        if (mysqli_num_rows($result) > 0) {
            $sqlScript .= "-- Dumping data for table `$table`\n";
            while ($row = mysqli_fetch_row($result)) {
                $sqlScript .= "INSERT INTO `$table` VALUES(";
                for ($j = 0; $j < mysqli_num_fields($result); $j++) {
                    if (isset($row[$j])) {
                        $sqlScript .= '"' . mysqli_real_escape_string($conn, $row[$j]) . '"';
                    } else {
                        $sqlScript .= 'NULL';
                    }
                    if ($j < (mysqli_num_fields($result) - 1)) {
                        $sqlScript .= ',';
                    }
                }
                $sqlScript .= ");\n";
            }
            $sqlScript .= "\n";
        }
    }
    
    $sqlScript .= "SET FOREIGN_KEY_CHECKS=1;\n";

    $backup_file_name = $db . '_backup_' . date('Y-m-d_H-i-s') . '.sql';
    header('Content-Type: application/octet-stream');
    header("Content-Transfer-Encoding: Binary");
    header("Content-disposition: attachment; filename=\"" . $backup_file_name . "\"");
    echo $sqlScript;
    exit;
}

// Ambil Data Settings
$settings = null;
try {
    $query_settings = mysqli_query($conn, "SELECT * FROM settings WHERE id=1");
    if (mysqli_num_rows($query_settings) == 0) {
        // Insert default jika belum ada data, mencegah error
        mysqli_query($conn, "INSERT INTO settings (id, store_name, store_address, store_phone, footer_note) VALUES (1, 'Lahaula Coffee', 'Alamat Toko', '', 'Terima Kasih')");
        $settings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM settings WHERE id=1"));
    } else {
        $settings = mysqli_fetch_assoc($query_settings);
        // Cek apakah kolom logo sudah ada, jika belum tambahkan (Migrasi Otomatis)
        if (!array_key_exists('logo', $settings)) {
            mysqli_query($conn, "ALTER TABLE settings ADD COLUMN logo VARCHAR(255) DEFAULT NULL");
            $settings['logo'] = null; // Set default untuk tampilan saat ini
        }
    }
} catch (mysqli_sql_exception $e) {
    // Jika tabel belum ada (Error 1146), buat tabelnya otomatis
    if ($e->getCode() == 1146) {
        $create_table = "CREATE TABLE IF NOT EXISTS settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            store_name VARCHAR(100) NOT NULL,
            store_address TEXT,
            store_phone VARCHAR(20),
            footer_note TEXT,
            logo VARCHAR(255) DEFAULT NULL
        )";
        mysqli_query($conn, $create_table);

        // Insert data default
        mysqli_query($conn, "INSERT INTO settings (id, store_name, store_address, store_phone, footer_note, logo) VALUES (1, 'Lahaula Coffee', 'Alamat Toko', '', 'Terima Kasih', NULL)");

        // Ambil data lagi
        $settings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM settings WHERE id=1"));
    } else {
        die("Database Error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Pengaturan Toko - Admin</title>
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
                <h1 class="text-2xl font-bold text-slate-800">Pengaturan Toko</h1>
                <p class="text-slate-500">Atur informasi yang tampil di struk belanja.</p>
            </div>
            <div class="h-10 w-10 bg-orange-200 rounded-full flex items-center justify-center font-bold text-orange-700 text-sm">AD</div>
        </header>

        <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100 max-w-2xl">
            <?php if ($message): ?>
                <div id="success-alert" class="bg-green-100 text-green-700 p-3 rounded-xl mb-6 text-sm font-bold border border-green-200 transition-opacity duration-500">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6" enctype="multipart/form-data">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Logo Toko</label>
                    <?php if (!empty($settings['logo'])): ?>
                        <div class="mb-3">
                            <img src="../../assets/images/<?= $settings['logo'] ?>" alt="Logo Toko" class="h-20 object-contain border border-slate-200 p-2 rounded-lg bg-slate-50">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="logo" class="w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100 transition border border-slate-200 rounded-xl cursor-pointer" accept="image/*">
                    <p class="text-xs text-slate-400 mt-1">Format: JPG, PNG, WEBP (Max. 2MB). Akan tampil di struk.</p>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Nama Toko / Coffee Shop</label>
                    <input type="text" name="store_name" value="<?= $settings['store_name'] ?>" class="w-full border border-slate-200 p-3 rounded-xl focus:ring-2 focus:ring-orange-500 outline-none" required>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Alamat Lengkap</label>
                    <textarea name="store_address" rows="3" class="w-full border border-slate-200 p-3 rounded-xl focus:ring-2 focus:ring-orange-500 outline-none" required><?= $settings['store_address'] ?></textarea>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Nomor Telepon</label>
                    <input type="text" name="store_phone" value="<?= $settings['store_phone'] ?>" class="w-full border border-slate-200 p-3 rounded-xl focus:ring-2 focus:ring-orange-500 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Catatan Kaki Struk (Footer)</label>
                    <input type="text" name="footer_note" value="<?= $settings['footer_note'] ?>" class="w-full border border-slate-200 p-3 rounded-xl focus:ring-2 focus:ring-orange-500 outline-none">
                    <p class="text-xs text-slate-400 mt-1">Contoh: "Terima Kasih", "Password Wifi: kopi123"</p>
                </div>

                <div class="pt-4 border-t border-slate-100">
                    <button type="submit" name="update_settings" class="bg-orange-600 hover:bg-orange-700 text-white font-bold py-3 px-8 rounded-xl transition shadow-lg shadow-orange-200">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>

        <!-- Backup Database -->
        <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100 max-w-2xl mt-8">
            <h2 class="text-lg font-bold text-slate-800 mb-4">Backup Database</h2>
            <p class="text-sm text-slate-500 mb-6">Unduh salinan database (SQL) untuk keperluan cadangan data.</p>
            <form method="POST">
                <button type="submit" name="backup_database" class="bg-slate-800 hover:bg-slate-900 text-white font-bold py-3 px-8 rounded-xl transition shadow-lg shadow-slate-200 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Download Backup
                </button>
            </form>
        </div>
    </main>

    <script>
        // Hapus pesan sukses setelah 3 detik
        const successAlert = document.getElementById('success-alert');
        if (successAlert) {
            setTimeout(() => {
                successAlert.style.opacity = '0';
                setTimeout(() => successAlert.remove(), 500); // Hapus elemen setelah transisi selesai
            }, 3000);
        }
    </script>
</body>

</html>