<?php
/**
 * OrderController
 * Logika Pemrosesan Pesanan
 */

class OrderController
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function createOrder($items, $total)
    {
        // Simpan order ke database
    }

    public function getOrders()
    {
        // Ambil semua pesanan
    }

    public function updateOrderStatus($orderId, $status)
    {
        // Update status pesanan
    }

    public function getOrderDetails($orderId)
    {
        // Ambil detail pesanan berdasarkan ID
    }
}
