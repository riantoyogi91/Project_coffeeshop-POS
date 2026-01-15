// Kitchen JavaScript

function checkRole() {
    const role = localStorage.getItem('currentRole');
    if (role !== 'kitchen') {
        window.location.href = '../../login.html';
    }
}

function logout() {
    localStorage.removeItem('currentRole');
    window.location.href = '../../login.html';
}

function loadDashboard() {
    checkRole();
    const orders = getOrders();
    const waiting = orders.filter(o => o.status === 'pending');
    const inProgress = orders.filter(o => o.status === 'preparing');
    const completed = orders.filter(o => o.status === 'completed' || o.status === 'ready');

    document.getElementById('waiting-orders').textContent = waiting.length;
    document.getElementById('in-progress-orders').textContent = inProgress.length;
    document.getElementById('completed-orders').textContent = completed.length;

    // Queue view
    const queueDiv = document.getElementById('order-queue');
    queueDiv.innerHTML = '';
    waiting.forEach(order => {
        const orderDiv = document.createElement('div');
        orderDiv.className = 'card';
        orderDiv.innerHTML = `
            <h4>Order #${order.id}</h4>
            <p>Items: ${order.items.map(i => `${i.name} x${i.qty}`).join(', ')}</p>
            <p>Total: $${order.total.toFixed(2)}</p>
            <button class="btn" onclick="acceptOrder(${order.id})">Accept</button>
        `;
        queueDiv.appendChild(orderDiv);
    });
}

function acceptOrder(orderId) {
    updateOrder(orderId, { status: 'preparing' });
    loadDashboard();
}

function loadPOS() {
    checkRole();
    loadIncomingOrders();
}

function loadIncomingOrders() {
    const orders = getOrders();
    const incoming = orders.filter(o => o.status === 'pending' || o.status === 'preparing');
    const container = document.getElementById('incoming-orders');
    container.innerHTML = '';
    incoming.forEach(order => {
        const orderDiv = document.createElement('div');
        orderDiv.className = 'card';
        orderDiv.innerHTML = `
            <h4>Order #${order.id} - ${order.status}</h4>
            <ul>
                ${order.items.map(item => `<li>${item.name} x${item.qty}</li>`).join('')}
            </ul>
            ${order.notes ? `<p>Notes: ${order.notes}</p>` : ''}
            <button class="btn" onclick="completeOrder(${order.id})">Complete</button>
        `;
        container.appendChild(orderDiv);
    });
}

function completeOrder(orderId) {
    updateOrder(orderId, { status: 'ready' });
    loadIncomingOrders();
}

function loadOrders() {
    checkRole();
    const orders = getOrders();
    const tabs = ['pending', 'preparing', 'ready', 'completed'];

    tabs.forEach(status => {
        const tabContent = document.getElementById(`${status}-orders`);
        tabContent.innerHTML = '';
        const filteredOrders = orders.filter(o => o.status === status);
        filteredOrders.forEach(order => {
            const orderDiv = document.createElement('div');
            orderDiv.className = 'card';
            orderDiv.innerHTML = `
                <h4>Order #${order.id} - ${order.customerName || 'Walk-in Customer'}</h4>
                <ul>
                    ${order.items.map(item => `<li>${item.name} x${item.qty}</li>`).join('')}
                </ul>
                <p>Total: $${order.total.toFixed(2)}</p>
                <p>Time: ${new Date(order.timestamp).toLocaleString()}</p>
                ${order.notes ? `<p><strong>Notes:</strong> ${order.notes}</p>` : ''}
                ${status === 'pending' ? `<button class="btn" onclick="acceptOrder(${order.id})">Accept</button>` : ''}
                ${status === 'preparing' ? `<button class="btn" onclick="completeOrder(${order.id})">Complete</button>` : ''}
            `;
            tabContent.appendChild(orderDiv);
        });
    });
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
    const sales = getSales();
    const today = new Date().toDateString();
    const todaySales = sales.filter(s => new Date(s.timestamp).toDateString() === today);

    // Items cooked
    const itemsCooked = {};
    todaySales.forEach(sale => {
        sale.items.forEach(item => {
            itemsCooked[item.productId] = (itemsCooked[item.productId] || 0) + item.qty;
        });
    });

    const products = getProducts();
    const cookedList = document.getElementById('items-cooked');
    cookedList.innerHTML = '';
    Object.entries(itemsCooked).forEach(([id, qty]) => {
        const product = products.find(p => p.id == id);
        if (product) {
            cookedList.innerHTML += `<li>${product.name}: ${qty}</li>`;
        }
    });

    // Most prepared
    const mostPrepared = Object.entries(itemsCooked).sort((a, b) => b[1] - a[1])[0];
    if (mostPrepared) {
        const product = products.find(p => p.id == mostPrepared[0]);
        document.getElementById('most-prepared').textContent = `${product ? product.name : 'Unknown'}: ${mostPrepared[1]}`;
    }

    // Busy hours
    const hourCounts = {};
    todaySales.forEach(sale => {
        const hour = new Date(sale.timestamp).getHours();
        hourCounts[hour] = (hourCounts[hour] || 0) + 1;
    });
    const busiestHour = Object.entries(hourCounts).sort((a, b) => b[1] - a[1])[0];
    if (busiestHour) {
        document.getElementById('busy-hours').textContent = `${busiestHour[0]}:00 - ${parseInt(busiestHour[0]) + 1}:00 (${busiestHour[1]} orders)`;
    }
}

function loadSettings() {
    checkRole();
    // Profile and notification settings can be added here
    // For now, just basic settings
}