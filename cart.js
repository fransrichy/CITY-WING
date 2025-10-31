class CartManager {
    constructor() {
        this.cart = JSON.parse(localStorage.getItem('citywing_cart')) || [];
        this.init();
    }

    init() {
        this.updateCartDisplay();
        this.attachEventListeners();
    }

    addToCart(product) {
        const existingItem = this.cart.find(item => item.product_id === product.product_id);
        
        if (existingItem) {
            existingItem.quantity += product.quantity;
        } else {
            this.cart.push({
                product_id: product.product_id,
                product_name: product.product_name,
                product_type: product.product_type,
                unit_price: parseFloat(product.unit_price),
                quantity: parseInt(product.quantity)
            });
        }
        
        this.saveCart();
        this.updateCartDisplay();
        this.showNotification('Item added to cart');
    }

    removeFromCart(productId) {
        this.cart = this.cart.filter(item => item.product_id !== productId);
        this.saveCart();
        this.updateCartDisplay();
    }

    updateQuantity(productId, quantity) {
        const item = this.cart.find(item => item.product_id === productId);
        if (item) {
            item.quantity = parseInt(quantity);
            if (item.quantity <= 0) {
                this.removeFromCart(productId);
            } else {
                this.saveCart();
                this.updateCartDisplay();
            }
        }
    }

    getTotal() {
        return this.cart.reduce((total, item) => total + (item.unit_price * item.quantity), 0);
    }

    saveCart() {
        localStorage.setItem('citywing_cart', JSON.stringify(this.cart));
    }

    updateCartDisplay() {
        const cartCount = document.getElementById('cart-count');
        const cartTotal = document.getElementById('cart-total');
        const cartItems = document.getElementById('cart-items');
        
        if (cartCount) cartCount.textContent = this.cart.length;
        if (cartTotal) cartTotal.textContent = `N$${this.getTotal().toFixed(2)}`;
        
        if (cartItems) {
            cartItems.innerHTML = this.cart.map(item => `
                <div class="cart-item">
                    <div class="item-details">
                        <h4>${item.product_name}</h4>
                        <p>N$${item.unit_price.toFixed(2)} x ${item.quantity}</p>
                    </div>
                    <div class="item-actions">
                        <button onclick="cartManager.updateQuantity('${item.product_id}', ${item.quantity - 1})">-</button>
                        <span>${item.quantity}</span>
                        <button onclick="cartManager.updateQuantity('${item.product_id}', ${item.quantity + 1})">+</button>
                        <button onclick="cartManager.removeFromCart('${item.product_id}')" class="remove-btn">Remove</button>
                    </div>
                </div>
            `).join('');
        }
    }

    attachEventListeners() {
        // Add to cart buttons
        document.querySelectorAll('.add-to-cart').forEach(button => {
            button.addEventListener('click', (e) => {
                const product = {
                    product_id: e.target.dataset.productId,
                    product_name: e.target.dataset.productName,
                    product_type: e.target.dataset.productType,
                    unit_price: parseFloat(e.target.dataset.price),
                    quantity: 1
                };
                this.addToCart(product);
            });
        });
    }

    showNotification(message) {
        // Create and show notification
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #0a6e48;
            color: white;
            padding: 15px 20px;
            border-radius: 5px;
            z-index: 1000;
            animation: slideIn 0.3s ease;
        `;
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    async checkout(customerData) {
        try {
            // Validate customer data
            if (!customerData.name || !customerData.email || !customerData.phone) {
                throw new Error('Please fill in all required customer details');
            }

            if (this.cart.length === 0) {
                throw new Error('Cart is empty');
            }

            const checkoutData = {
                customer_name: customerData.name,
                customer_email: customerData.email,
                customer_phone: customerData.phone,
                items: this.cart
            };

            // Show loading state
            this.showLoading('Processing checkout...');

            const response = await fetch('checkout.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(checkoutData)
            });

            const result = await response.json();

            if (result.success) {
                // Redirect to PayGate
                this.submitToPayGate(result.paygate_data, result.paygate_url);
            } else {
                throw new Error(result.message || 'Checkout failed');
            }

        } catch (error) {
            this.hideLoading();
            this.showNotification('Error: ' + error.message);
            console.error('Checkout error:', error);
        }
    }

    submitToPayGate(paygateData, paygateUrl) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = paygateUrl;
        form.style.display = 'none';

        Object.keys(paygateData).forEach(key => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = paygateData[key];
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
    }

    showLoading(message) {
        const loading = document.createElement('div');
        loading.id = 'checkout-loading';
        loading.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            color: white;
            font-size: 18px;
        `;
        loading.innerHTML = `
            <div style="text-align: center;">
                <div class="spinner" style="border: 4px solid #f3f3f3; border-top: 4px solid #064b2f; border-radius: 50%; width: 50px; height: 50px; animation: spin 2s linear infinite; margin: 0 auto 20px;"></div>
                <p>${message}</p>
            </div>
        `;
        document.body.appendChild(loading);
    }

    hideLoading() {
        const loading = document.getElementById('checkout-loading');
        if (loading) loading.remove();
    }

    clearCart() {
        this.cart = [];
        this.saveCart();
        this.updateCartDisplay();
    }
}

// Initialize cart manager
const cartManager = new CartManager();

// Example checkout form handler
document.addEventListener('DOMContentLoaded', function() {
    const checkoutForm = document.getElementById('checkout-form');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const customerData = {
                name: document.getElementById('customer-name').value,
                email: document.getElementById('customer-email').value,
                phone: document.getElementById('customer-phone').value
            };

            cartManager.checkout(customerData);
        });
    }
});