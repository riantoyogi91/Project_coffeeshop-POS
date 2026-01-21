-- Buat Database
CREATE DATABASE IF NOT EXISTS coffee_shop_pos;
USE coffee_shop_pos;

-- 1. Tabel Users (Pengguna)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'cashier', 'kitchen') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Tabel Products (Produk/Menu)
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category ENUM('Coffee', 'Non-Coffee', 'Food', 'Snack') NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    stock INT DEFAULT 0,
    image VARCHAR(255) DEFAULT 'default.png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 3. Tabel Orders (Pesanan Utama)
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 4. Tabel Order Items (Detail Item dalam Pesanan)
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL, -- Harga saat transaksi terjadi (snapshot)
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
);

-- 5. Tabel Settings (Pengaturan Toko)
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    store_name VARCHAR(100) DEFAULT 'Coffee Shop',
    store_address TEXT,
    store_phone VARCHAR(20),
    footer_note TEXT,
    logo VARCHAR(255) DEFAULT NULL
);

-- 6. Tabel Activity Logs (Log Aktivitas Login)
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- SEEDING DATA (Data Awal) --

-- Insert Default Users
-- Password untuk semua user di bawah adalah: password
-- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
INSERT INTO users (username, password, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('kasir', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cashier'),
('dapur', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'kitchen');

-- Insert Default Settings
INSERT INTO settings (store_name, store_address, store_phone, footer_note) VALUES 
('My Coffee Shop', 'Jl. Kopi No. 1, Jakarta', '0812-3456-7890', 'Terima kasih telah berkunjung!');

-- Insert Sample Products
INSERT INTO products (name, category, price, stock, image) VALUES 
('Americano', 'Coffee', 18000, 100, 'default.png'),
('Cappuccino', 'Coffee', 22000, 50, 'default.png'),
('Latte', 'Coffee', 24000, 50, 'default.png'),
('Matcha Latte', 'Non-Coffee', 25000, 40, 'default.png'),
('Nasi Goreng', 'Food', 30000, 20, 'default.png'),
('Kentang Goreng', 'Snack', 15000, 30, 'default.png');
