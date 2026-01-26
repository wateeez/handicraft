<?php
$pageTitle = "Shopping Cart";
// Get cart items
$cartItems = getCartItems();
$subtotal = 0;
// Get shipping methods
$shippingMethods = $db->fetchAll("SELECT * FROM shipping_methods WHERE is_active = 1 ORDER BY display_order ASC");
$selectedShipping = isset($_SESSION['cart_shipping_method']) ? $_SESSION['cart_shipping_method'] : ($shippingMethods[0]['id'] ?? null);
$shippingCost = 0;
if ($selectedShipping) {
    $shippingCost = calculateCartShipping($cartItems, $selectedShipping);
}
?>

<div class="cart-page">
    <div class="container">
        <h1>Shopping Cart</h1>

        <?php if ($cartItems): ?>
            <div class="cart-content">
                <div class="cart-items">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cartItems as $item): 
                                $itemSubtotal = $item['price'] * $item['quantity'];
                                $subtotal += $itemSubtotal;
                            ?>
                                <tr>
                                    <td class="product-info">
                                        <div class="product-cell">
                                            <a href="/shop/product/<?php echo $item['slug']; ?>">
                                                <?php if ($item['image']): ?>
                                                    <img src="/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                                <?php else: ?>
                                                    <div class="product-placeholder-small">
                                                        <i class="fas fa-image"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <span><?php echo htmlspecialchars($item['name']); ?></span>
                                            </a>
                                        </div>
                                    </td>
                                    <td class="price"><?php echo formatPrice($item['price']); ?></td>
                                    <td class="quantity">
                                        <form method="POST" action="/shop/cart/update" class="quantity-form">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="cart_item_id" value="<?php echo $item['id']; ?>">
                                            <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                                   min="1" 
                                                   onchange="this.form.submit()">
                                        </form>
                                    </td>
                                    <td class="subtotal"><?php echo formatPrice($itemSubtotal); ?></td>
                                    <td class="actions">
                                        <form method="POST" action="/shop/cart/remove" style="display: inline;">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="cart_item_id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" class="btn-remove" onclick="return confirm('Remove this item from cart?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="cart-actions">
                        <a href="/shop/products" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Continue Shopping
                        </a>
                    </div>
                </div>

                <!-- Cart Summary -->
                <div class="cart-summary">
                    <h3>Cart Summary</h3>
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span><?php echo formatPrice($subtotal); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Tax (estimated):</span>
                        <span><?php echo formatPrice(calculateTax($subtotal)); ?></span>
                    </div>
                    <form id="shippingForm" method="POST" action="/api/cart-shipping.php" style="margin-bottom:1rem;">
                        <div class="summary-row">
                            <span>Shipping:</span>
                            <select name="shipping_method" id="shippingMethodSelect" onchange="updateShipping()" style="width: 60%;">
                                <?php foreach ($shippingMethods as $method): 
                                    $cost = calculateCartShipping($cartItems, $method['id']);
                                ?>
                                    <option value="<?php echo $method['id']; ?>" data-cost="<?php echo $cost; ?>" <?php echo $selectedShipping == $method['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($method['name']); ?> (<?php echo formatPrice($cost); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                    <div class="summary-row">
                        <span>Shipping Cost:</span>
                        <span id="shippingCostDisplay"><?php echo formatPrice($shippingCost); ?></span>
                    </div>
                    <div class="summary-row total">
                        <span>Estimated Total:</span>
                        <span id="cartTotalDisplay"><?php echo formatPrice($subtotal + calculateTax($subtotal) + $shippingCost); ?></span>
                    </div>
                    <a href="/shop/checkout" class="btn btn-primary btn-block">
                        Proceed to Checkout <i class="fas fa-arrow-right"></i>
                    </a>
                    
                    <!-- Coupon Code -->
                    <div class="coupon-section">
                        <h4>Have a Coupon?</h4>
                        <form id="couponForm" class="coupon-form">
                            <input type="text" name="coupon_code" placeholder="Enter coupon code" required>
                            <button type="button" class="btn btn-secondary" onclick="applyCouponCode()">Apply</button>
                        </form>
                        <div id="couponMessage"></div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h2>Your cart is empty</h2>
                <p>Add some products to get started!</p>
                <a href="/shop/products" class="btn btn-primary">Start Shopping</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function updateShipping() {
    var select = document.getElementById('shippingMethodSelect');
    var selected = select.options[select.selectedIndex];
    var cost = parseFloat(selected.getAttribute('data-cost'));
    var subtotal = <?php echo $subtotal; ?>;
    var tax = <?php echo calculateTax($subtotal); ?>;
    document.getElementById('shippingCostDisplay').textContent = '<?php echo CURRENCY_SYMBOL; ?>' + cost.toFixed(2);
    document.getElementById('cartTotalDisplay').textContent = '<?php echo CURRENCY_SYMBOL; ?>' + (subtotal + tax + cost).toFixed(2);
    // Persist selection via POST
    var form = document.getElementById('shippingForm');
    var xhr = new XMLHttpRequest();
    xhr.open('POST', form.action, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    var params = 'shipping_method=' + encodeURIComponent(select.value);
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            // Reload cart summary to reflect new shipping method
            location.reload();
        }
    };
    xhr.send(params);
}
function applyCouponCode() {
    const form = document.getElementById('couponForm');
    const formData = new FormData(form);
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    fetch('/shop/api/coupon/apply', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            coupon_code: formData.get('coupon_code')
        })
    })
    .then(response => response.json())
    .then(data => {
        const messageDiv = document.getElementById('couponMessage');
        if (data.success) {
            messageDiv.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
            setTimeout(() => location.reload(), 1000);
        } else {
            messageDiv.innerHTML = '<div class="alert alert-error">' + data.message + '</div>';
        }
    });
}
</script>
