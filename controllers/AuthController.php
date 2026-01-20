<?php
require_once __DIR__ . '/../config/database.php';

function login($username, $password, $conn) {
    // Menghindari SQL Injection
    $username = mysqli_real_escape_string($conn, $username);
    
    $query = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);
        
        // Verifikasi password (menggunakan password_hash di database)
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];

            // Redirect berdasarkan role
            header("Location: views/" . $user['role'] . "/dashboard.php");
            exit;
        }
    }
    return "Username atau Password salah!";
}
?>