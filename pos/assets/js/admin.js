// Admin JavaScript

function checkRole() {
    const role = localStorage.getItem('currentRole');
    if (role !== 'admin') {
        window.location.href = '../../login.html';
    }
}

function logout() {
    localStorage.removeItem('currentRole');
    window.location.href = '../../login.html';
}

function loadDashboard() {
    checkRole();
    const todaySales = getTodaySales();
    const monthlySales = getMonthlySales();
    const orders = getOrders();
    const products = getProducts();

    document.getElementById('total-sales-today').textContent = `$${getTotalSales(todaySales).toFixed(2)}`;
    document.getElementById('monthly-sales').textContent = `$${getTotalSales(monthlySales).toFixed(2)}`;
    document.getElementById('orders-count').textContent = orders.length;

    // Simple chart
    const chartData = getBestSellingProducts();
    const chartContainer = document.getElementById('sales-chart');
    chartContainer.innerHTML = '';
    chartData.forEach(item => {
        const bar = document.createElement('div');
        bar.className = 'bar';
        bar.style.height = `${item.count * 20}px`;
        bar.setAttribute('data-value', item.count);
        bar.innerHTML = `<div style="position: absolute; bottom: -20px; left: 50%; transform: translateX(-50%); font-size: 10px;">${item.name}</div>`;
        chartContainer.appendChild(bar);
    });

    // Inventory status
    const lowStock = products.filter(p => p.stock < 10);
    const inventoryList = document.getElementById('inventory-status');
    inventoryList.innerHTML = '';
    lowStock.forEach(product => {
        const li = document.createElement('li');
        li.textContent = `${product.name}: ${product.stock} left`;
        inventoryList.appendChild(li);
    });
}

function loadSales() {
    checkRole();
    const sales = getSales();
    const table = document.getElementById('sales-table');
    table.innerHTML = '<tr><th>Date</th><th>Total</th><th>Items</th></tr>';
    sales.forEach(sale => {
        const row = table.insertRow();
        row.insertCell(0).textContent = new Date(sale.timestamp).toLocaleDateString();
        row.insertCell(1).textContent = `$${sale.total.toFixed(2)}`;
        row.insertCell(2).textContent = sale.items.length;
    });
}

function exportSales() {
    const sales = getSales();
    const exportData = sales.map(sale => ({
        Date: new Date(sale.timestamp).toLocaleDateString(),
        Total: sale.total,
        Items: sale.items.length
    }));
    exportToCSV(exportData, 'sales.csv');
}

function loadMenu() {
    checkRole();
    const products = getProducts();
    const table = document.getElementById('menu-table');
    table.innerHTML = '<tr><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Actions</th></tr>';
    products.forEach(product => {
        const row = table.insertRow();
        row.insertCell(0).textContent = product.name;
        row.insertCell(1).textContent = product.category;
        row.insertCell(2).textContent = `$${product.price.toFixed(2)}`;
        row.insertCell(3).textContent = product.stock;
        const actions = row.insertCell(4);
        actions.innerHTML = `
            <button class="btn btn-secondary" onclick="editProduct(${product.id})">Edit</button>
            <button class="btn btn-danger" onclick="deleteProduct(${product.id})">Delete</button>
        `;
    });
}

function addProduct() {
    const name = document.getElementById('product-name').value;
    const category = document.getElementById('product-category').value;
    const price = parseFloat(document.getElementById('product-price').value);
    const stock = parseInt(document.getElementById('product-stock').value);
    const image = document.getElementById('product-image').value;

    if (name && category && price && stock) {
        addProduct({ name, category, price, stock, image });
        loadMenu();
        clearForm();
    }
}

function editProduct(id) {
    const products = getProducts();
    const product = products.find(p => p.id == id);
    if (product) {
        document.getElementById('product-name').value = product.name;
        document.getElementById('product-category').value = product.category;
        document.getElementById('product-price').value = product.price;
        document.getElementById('product-stock').value = product.stock;
        document.getElementById('product-image').value = product.image;
        document.getElementById('add-product-btn').textContent = 'Update Product';
        document.getElementById('add-product-btn').onclick = () => updateProduct(id);
    }
}

function updateProduct(id) {
    const name = document.getElementById('product-name').value;
    const category = document.getElementById('product-category').value;
    const price = parseFloat(document.getElementById('product-price').value);
    const stock = parseInt(document.getElementById('product-stock').value);
    const image = document.getElementById('product-image').value;

    if (name && category && price && stock) {
        updateProduct(id, { name, category, price, stock, image });
        loadMenu();
        clearForm();
    }
}

function deleteProduct(id) {
    if (confirm('Are you sure you want to delete this product?')) {
        deleteProduct(id);
        loadMenu();
    }
}

function clearForm() {
    document.getElementById('product-name').value = '';
    document.getElementById('product-category').value = '';
    document.getElementById('product-price').value = '';
    document.getElementById('product-stock').value = '';
    document.getElementById('product-image').value = '';
    document.getElementById('add-product-btn').textContent = 'Add Product';
    document.getElementById('add-product-btn').onclick = addProduct;
}

function loadOrders() {
    checkRole();
    const orders = getOrders();
    const table = document.getElementById('orders-table');
    table.innerHTML = '<tr><th>ID</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th><th>Actions</th></tr>';
    orders.forEach(order => {
        const row = table.insertRow();
        row.insertCell(0).textContent = order.id;
        row.insertCell(1).textContent = order.customerName || 'Walk-in Customer';
        row.insertCell(2).textContent = order.items.length;
        row.insertCell(3).textContent = `$${order.total.toFixed(2)}`;
        const statusCell = row.insertCell(4);
        statusCell.innerHTML = `<span class="status-badge status-${order.status}">${order.status}</span>`;
        const actions = row.insertCell(5);
        actions.innerHTML = `<button class="btn btn-secondary" onclick="viewOrder(${order.id})">View</button>`;
    });
}

function viewOrder(id) {
    const orders = getOrders();
    const order = orders.find(o => o.id == id);
    if (order) {
        const itemsList = order.items.map(i => `${i.name} x${i.qty}`).join(', ');
        const notesText = order.notes ? `\nNotes: ${order.notes}` : '';
        alert(`Order ${id}\nCustomer: ${order.customerName || 'Walk-in Customer'}\nItems: ${itemsList}\nTotal: $${order.total.toFixed(2)}\nStatus: ${order.status}${notesText}`);
    }
}

function loadReports() {
    checkRole();
    const sales = getSales();
    const totalSales = getTotalSales(sales);
    const totalOrders = sales.length;
    const avgOrder = totalOrders > 0 ? totalSales / totalOrders : 0;

    document.getElementById('report-total-sales').textContent = `$${totalSales.toFixed(2)}`;
    document.getElementById('report-total-orders').textContent = totalOrders;
    document.getElementById('report-avg-order').textContent = `$${avgOrder.toFixed(2)}`;
}

function exportReports() {
    const sales = getSales();
    const exportData = sales.map(sale => ({
        Date: new Date(sale.timestamp).toLocaleDateString(),
        Total: sale.total,
        Items: sale.items.length
    }));
    exportToCSV(exportData, 'reports.csv');
}

function filterSales() {
    // For now, just reload sales
    loadSales();
}

function filterMenu() {
    // For now, just reload menu
    loadMenu();
}