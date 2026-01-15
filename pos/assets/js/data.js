// Coffee Shop POS Data Management

// Initialize data if not exists
function initData() {
    if (!localStorage.getItem('products')) {
        const defaultProducts = [
            { id: 1, name: 'Espresso', category: 'Coffee', price: 3.50, stock: 100, image: 'espresso.jpg' },
            { id: 2, name: 'Cappuccino', category: 'Coffee', price: 4.00, stock: 80, image: 'cappuccino.jpg' },
            { id: 3, name: 'Latte', category: 'Coffee', price: 4.50, stock: 90, image: 'latte.jpg' },
            { id: 4, name: 'Croissant', category: 'Pastry', price: 2.50, stock: 50, image: 'croissant.jpg' },
            { id: 5, name: 'Muffin', category: 'Pastry', price: 3.00, stock: 40, image: 'muffin.jpg' },
            { id: 6, name: 'Sandwich', category: 'Food', price: 6.00, stock: 30, image: 'sandwich.jpg' }
        ];
        localStorage.setItem('products', JSON.stringify(defaultProducts));
    }

    if (!localStorage.getItem('orders')) {
        localStorage.setItem('orders', JSON.stringify([]));
    }

    if (!localStorage.getItem('sales')) {
        localStorage.setItem('sales', JSON.stringify([]));
    }

    if (!localStorage.getItem('settings')) {
        const defaultSettings = {
            storeName: 'Coffee Shop POS',
            taxRate: 0.08,
            paymentMethods: ['Cash', 'Card', 'Digital Wallet'],
            receiptFooter: 'Thank you for your visit!'
        };
        localStorage.setItem('settings', JSON.stringify(defaultSettings));
    }
}

// Products
function getProducts() {
    return JSON.parse(localStorage.getItem('products')) || [];
}

function saveProducts(products) {
    localStorage.setItem('products', JSON.stringify(products));
}

function addProduct(product) {
    const products = getProducts();
    product.id = Date.now();
    products.push(product);
    saveProducts(products);
}

function updateProduct(id, updatedProduct) {
    const products = getProducts();
    const index = products.findIndex(p => p.id == id);
    if (index !== -1) {
        products[index] = { ...products[index], ...updatedProduct };
        saveProducts(products);
    }
}

function deleteProduct(id) {
    const products = getProducts();
    const filtered = products.filter(p => p.id != id);
    saveProducts(filtered);
}

// Orders
function getOrders() {
    return JSON.parse(localStorage.getItem('orders')) || [];
}

function saveOrders(orders) {
    localStorage.setItem('orders', JSON.stringify(orders));
}

function addOrder(order) {
    const orders = getOrders();
    order.id = Date.now();
    order.timestamp = new Date().toISOString();
    orders.push(order);
    saveOrders(orders);
    return order.id;
}

function updateOrder(id, updatedOrder) {
    const orders = getOrders();
    const index = orders.findIndex(o => o.id == id);
    if (index !== -1) {
        orders[index] = { ...orders[index], ...updatedOrder };
        saveOrders(orders);
    }
}

// Sales
function getSales() {
    return JSON.parse(localStorage.getItem('sales')) || [];
}

function saveSales(sales) {
    localStorage.setItem('sales', JSON.stringify(sales));
}

function addSale(sale) {
    const sales = getSales();
    sale.id = Date.now();
    sale.timestamp = new Date().toISOString();
    sales.push(sale);
    saveSales(sales);
}

// Settings
function getSettings() {
    return JSON.parse(localStorage.getItem('settings')) || {};
}

function saveSettings(settings) {
    localStorage.setItem('settings', JSON.stringify(settings));
}

// Utility functions
function getTodaySales() {
    const sales = getSales();
    const today = new Date().toDateString();
    return sales.filter(s => new Date(s.timestamp).toDateString() === today);
}

function getMonthlySales() {
    const sales = getSales();
    const now = new Date();
    const currentMonth = now.getMonth();
    const currentYear = now.getFullYear();
    return sales.filter(s => {
        const date = new Date(s.timestamp);
        return date.getMonth() === currentMonth && date.getFullYear() === currentYear;
    });
}

function getTotalSales(sales) {
    return sales.reduce((total, sale) => total + sale.total, 0);
}

function getBestSellingProducts() {
    const sales = getSales();
    const productCounts = {};
    sales.forEach(sale => {
        sale.items.forEach(item => {
            productCounts[item.productId] = (productCounts[item.productId] || 0) + item.qty;
        });
    });
    const products = getProducts();
    return Object.entries(productCounts)
        .map(([id, count]) => {
            const product = products.find(p => p.id == id);
            return { name: product ? product.name : 'Unknown', count };
        })
        .sort((a, b) => b.count - a.count)
        .slice(0, 5);
}

function exportToCSV(data, filename) {
    const csv = data.map(row => Object.values(row).join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
}

// Initialize on load
initData();