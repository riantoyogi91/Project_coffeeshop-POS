// Cashier JavaScript

function checkRole() {
    const role = localStorage.getItem('currentRole');
    if (role !== 'cashier') {
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
    const orders = getOrders();

    document.getElementById('today-sales').textContent = `$${getTotalSales(todaySales).toFixed(2)}`;
    document.getElementById('orders-count').textContent = orders.length;
    document.getElementById('active-orders').textContent = orders.filter(o => o.status !== 'completed').length;

    // Payment summary
    const paymentSummary = {};
    todaySales.forEach(sale => {
        const method = sale.paymentMethod || 'Cash';
        paymentSummary[method] = (paymentSummary[method] || 0) + sale.total;
    });
    const summaryDiv = document.getElementById('payment-summary');
    summaryDiv.innerHTML = '';
    Object.entries(paymentSummary).forEach(([method, amount]) => {
        summaryDiv.innerHTML += `<p>${method}: $${amount.toFixed(2)}</p>`;
    });
}

function loadPOS() {
    checkRole();
    loadProducts();
    loadCart();
}

function loadProducts() {
    const products = getProducts();
    const grid = document.getElementById('products-grid');
    grid.innerHTML = '';
    products.forEach(product => {
        const card = document.createElement('div');
        card.className = 'product-card';
        card.innerHTML = `
            <img src="assets/img/${product.image}" alt="${product.name}" onerror="this.src='assets/img/placeholder.jpg'">
            <h4>${product.name}</h4>
            <p class="price">$${product.price.toFixed(2)}</p>
            <button class="btn" onclick="addToCart(${product.id})">Add to Cart</button>
        `;
        grid.appendChild(card);
    });
}

let cart = [];

function addToCart(productId) {
    const products = getProducts();
    const product = products.find(p => p.id == productId);
    if (product) {
        const existing = cart.find(item => item.id == productId);
        if (existing) {
            existing.qty++;
        } else {
            cart.push({ ...product, qty: 1 });
        }
        loadCart();
    }
}

function loadCart() {
    const cartDiv = document.getElementById('cart-items');
    cartDiv.innerHTML = '';
    let subtotal = 0;
    cart.forEach(item => {
        subtotal += item.price * item.qty;
        const itemDiv = document.createElement('div');
        itemDiv.className = 'cart-item';
        itemDiv.innerHTML = `
            <span>${item.name} - $${item.price.toFixed(2)}</span>
            <div class="qty-controls">
                <button onclick="changeQty(${item.id}, -1)">-</button>
                <span>${item.qty}</span>
                <button onclick="changeQty(${item.id}, 1)">+</button>
            </div>
            <span>$${(item.price * item.qty).toFixed(2)}</span>
            <button class="btn btn-danger" onclick="removeFromCart(${item.id})">Remove</button>
        `;
        cartDiv.appendChild(itemDiv);
    });

    const settings = getSettings();
    const tax = subtotal * (settings.taxRate || 0);
    const total = subtotal + tax;

    document.getElementById('subtotal').textContent = `$${subtotal.toFixed(2)}`;
    document.getElementById('tax').textContent = `$${tax.toFixed(2)}`;
    document.getElementById('total').textContent = `$${total.toFixed(2)}`;
}

function changeQty(productId, change) {
    const item = cart.find(item => item.id == productId);
    if (item) {
        item.qty += change;
        if (item.qty <= 0) {
            cart = cart.filter(item => item.id != productId);
        }
        loadCart();
    }
}

function removeFromCart(productId) {
    cart = cart.filter(item => item.id != productId);
    loadCart();
}

function charge() {
    if (cart.length === 0) {
        alert('Cart is empty!');
        return;
    }

    const paymentMethod = document.getElementById('payment-method').value;
    const customerName = document.getElementById('customer-name').value.trim();
    const orderNotes = document.getElementById('order-notes').value.trim();
    const settings = getSettings();
    const subtotal = cart.reduce((sum, item) => sum + item.price * item.qty, 0);
    const tax = subtotal * (settings.taxRate || 0);
    const total = subtotal + tax;

    // Create order
    const order = {
        items: cart.map(item => ({ productId: item.id, name: item.name, qty: item.qty, price: item.price })),
        total: total,
        status: 'pending',
        paymentMethod: paymentMethod,
        customerName: customerName || 'Walk-in Customer',
        notes: orderNotes
    };
    const orderId = addOrder(order);

    // Create sale
    addSale({
        orderId: orderId,
        items: cart.map(item => ({ productId: item.id, qty: item.qty })),
        total: total,
        paymentMethod: paymentMethod
    });

    // Update stock
    cart.forEach(item => {
        const products = getProducts();
        const product = products.find(p => p.id == item.id);
        if (product) {
            product.stock -= item.qty;
            updateProduct(item.id, { stock: product.stock });
        }
    });

    // Generate receipt
    generateReceipt(orderId, subtotal, tax, total, paymentMethod, customerName || 'Walk-in Customer', orderNotes);

    // Clear cart and form fields
    cart = [];
    document.getElementById('customer-name').value = '';
    document.getElementById('order-notes').value = '';
    loadCart();
    loadProducts();
    alert('Order completed!');
}

function generateReceipt(orderId, subtotal, tax, total, paymentMethod, customerName, notes) {
    const receipt = `
${getSettings().storeName}
Order ID: ${orderId}
Date: ${new Date().toLocaleString()}
Customer: ${customerName}
${notes ? `Notes: ${notes}` : ''}

Items:
${cart.map(item => `${item.name} x${item.qty} - $${(item.price * item.qty).toFixed(2)}`).join('\n')}

Subtotal: $${subtotal.toFixed(2)}
Tax: $${tax.toFixed(2)}
Total: $${total.toFixed(2)}
Payment: ${paymentMethod}

${getSettings().receiptFooter}
    `;
    document.getElementById('receipt-content').textContent = receipt;
    document.getElementById('receipt-preview').style.display = 'block';
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
        actions.innerHTML = `
            <button class="btn btn-secondary" onclick="changeStatus(${order.id}, 'preparing')">Preparing</button>
            <button class="btn" onclick="changeStatus(${order.id}, 'ready')">Ready</button>
            <button class="btn btn-danger" onclick="changeStatus(${order.id}, 'completed')">Completed</button>
        `;
    });
}

function changeStatus(orderId, status) {
    updateOrder(orderId, { status });
    loadOrders();
}

function loadMenus() {
    checkRole();
    const products = getProducts();
    const grid = document.getElementById('menus-grid');
    grid.innerHTML = '';
    products.forEach(product => {
        const card = document.createElement('div');
        card.className = 'product-card';
        card.innerHTML = `
            <img src="assets/img/${product.image}" alt="${product.name}" onerror="this.src='assets/img/placeholder.jpg'">
            <h4>${product.name}</h4>
            <p>Stock: ${product.stock}</p>
            <p class="price">$${product.price.toFixed(2)}</p>
        `;
        grid.appendChild(card);
    });
}

function loadSales() {
    checkRole();
    const todaySales = getTodaySales();
    const table = document.getElementById('sales-table');
    table.innerHTML = '<tr><th>Time</th><th>Total</th><th>Items</th><th>Payment</th></tr>';
    todaySales.forEach(sale => {
        const row = table.insertRow();
        row.insertCell(0).textContent = new Date(sale.timestamp).toLocaleTimeString();
        row.insertCell(1).textContent = `$${sale.total.toFixed(2)}`;
        row.insertCell(2).textContent = sale.items.length;
        row.insertCell(3).textContent = sale.paymentMethod || 'Cash';
    });

    const total = getTotalSales(todaySales);
    document.getElementById('shift-total').textContent = `$${total.toFixed(2)}`;
}

function filterProducts() {
    // For now, just reload products
    loadProducts();
}