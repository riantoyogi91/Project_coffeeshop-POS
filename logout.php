<?php
require_once 'config/database.php';
/**
 * Logout
 */


// Catat Log Logout jika user sedang login
if (isset($_SESSION['user_id'])) {
    try {
        $uid = $_SESSION['user_id'];
        mysqli_query($conn, "INSERT INTO activity_logs (user_id, action) VALUES ($uid, 'logout')");
    } catch (Exception $e) {
        // Abaikan error log
    }
}

session_destroy();

header('Location: index.php');
exit;
