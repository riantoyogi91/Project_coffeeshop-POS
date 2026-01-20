-- Buat Database
CREATE DATABASE IF NOT EXISTS db_coffeeshop;
USE db_coffeeshop;

-- Tabel Users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'cashier', 'kitchen') NOT NULL,
    avatar VARCHAR(255) DEFAULT 'default_user.png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Products
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    image VARCHAR(255) DEFAULT 'default.png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Orders (Untuk Transaksi)
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Tabel Order Items (Detail Pesanan)
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Seed Data Users (Password default untuk semua user adalah: 'password')
-- Hash password dihasilkan menggunakan password_hash('password', PASSWORD_DEFAULT)
INSERT INTO users (username, password, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('kasir', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cashier'),
('dapur', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'kitchen');

-- Seed Data Products
INSERT INTO products (name, category, price) VALUES 
('Caffè Latte', 'Coffee', 24000), ('Cappuccino', 'Coffee', 22000), ('Americano', 'Coffee', 18000), ('Espresso', 'Coffee', 15000), ('Caramel Macchiato', 'Coffee', 28000),
('Matcha Latte', 'Non-Coffee', 25000), ('Chocolate', 'Non-Coffee', 23000), ('Red Velvet', 'Non-Coffee', 24000),
('Croissant', 'Food', 18000), ('Beef Burger', 'Food', 35000), ('French Fries', 'Snack', 15000), ('Onion Rings', 'Snack', 18000);