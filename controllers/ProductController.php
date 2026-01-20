<?php
/**
 * ProductController
 * Logika Manajemen Produk
 */

class ProductController
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAllProducts()
    {
        // Ambil semua produk dari database
    }

    public function addProduct($name, $price, $category)
    {
        // Tambah produk baru
    }

    public function updateProduct($productId, $name, $price, $category)
    {
        // Update data produk
    }

    public function deleteProduct($productId)
    {
        // Hapus produk
    }

    public function getProductByCategory($category)
    {
        // Ambil produk berdasarkan kategori
    }
}
