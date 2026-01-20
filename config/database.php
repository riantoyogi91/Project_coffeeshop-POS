<?php
// Memulai session untuk menyimpan data login
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_coffeeshop";

// MySQLi Connection
$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Set charset to UTF8
mysqli_set_charset($conn, "utf8");

// Mengaktifkan exception untuk error query mysqli agar bisa ditangkap dengan try-catch
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
