<?php
$pageTitle = "Checkout";

// Get cart items
$cartItems = getCartItems();

if (empty($cartItems)) {
    echo '<script>window.location.href="/shop/cart";</script>';
    exit;
}

// Get shipping methods
$shippingMethods = $db->fetchAll("SELECT * FROM shipping_methods WHERE is_active = 1 ORDER BY display_order ASC");

// Get payment providers
$paymentProviders = $db->fetchAll("SELECT * FROM payment_providers WHERE is_active = 1 ORDER BY display_order ASC");

// Get tax rates
$taxRates = $db->fetchAll("SELECT * FROM tax_rates WHERE is_active = 1 ORDER BY country ASC");

// Calculate subtotal
$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
?>

<div class="checkout-page">
    <div class="container">
        <h1>Checkout</h1>

        <?php if (Session::has('error')): ?>
            <div class="alert alert-danger">
                <?php echo Session::get('error'); ?>
            </div>
        <?php endif; ?>

        <?php if (Session::has('success')): ?>
            <div class="alert alert-success">
                <?php echo Session::get('success'); ?>
            </div>
        <?php endif; ?>

        <div class="checkout-content">
            <!-- Checkout Form -->
            <div class="checkout-form-section">
                <form method="POST" action="/shop/checkout" id="checkoutForm">
                    <?php echo csrf_field(); ?>
                    <!-- Customer Information -->
                    <div class="form-section">
                        <h2><i class="fas fa-user"></i> Customer Information</h2>
                        <div class="form-row">
                            <div class="form-group">
                                <label>First Name *</label>
                                <input type="text" name="first_name" required value="<?php echo old('first_name'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Last Name *</label>
                                <input type="text" name="last_name" required value="<?php echo old('last_name'); ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Email Address *</label>
                                <input type="email" name="email" required value="<?php echo old('email'); ?>">
                            </div>
                            <div class="form-group">
                                <label>Phone Number *</label>
                                <input type="tel" name="phone" required value="<?php echo old('phone'); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Billing Address -->
                    <div class="form-section">
                        <h2><i class="fas fa-file-invoice"></i> Billing Address</h2>
                        <div class="form-group">
                            <label>Street Address *</label>
                            <input type="text" name="address" required value="<?php echo old('address'); ?>">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>City *</label>
                                <input type="text" name="city" required value="<?php echo old('city'); ?>">
                            </div>
                            <div class="form-group">
                                <label>State/Province *</label>
                                <input type="text" name="state" required value="<?php echo old('state'); ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Country *</label>
                                <input type="text" name="country" required value="<?php echo old('country'); ?>">
                            </div>
                            <div class="form-group">
                                <label>ZIP/Postal Code *</label>
                                <input type="text" name="zip" required value="<?php echo old('zip'); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Address -->
                    <div class="form-section">
                        <h2><i class="fas fa-shipping-fast"></i> Shipping Address</h2>
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="same_as_billing" id="sameAsBilling" onchange="toggleShippingAddress()">
                                Same as billing address
                            </label>
                        </div>
                        <div id="shippingAddressFields">
                            <div class="form-group">
                                <label>Street Address *</label>
                                <input type="text" name="shipping_address" id="shipping_address" value="<?php echo $_POST['shipping_address'] ?? ''; ?>">
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>City *</label>
                                    <input type="text" name="shipping_city" id="shipping_city" value="<?php echo $_POST['shipping_city'] ?? ''; ?>">
                                </div>
                                <div class="form-group">
                                    <label>State/Province</label>
                                    <input type="text" name="shipping_state" id="shipping_state" value="<?php echo $_POST['shipping_state'] ?? ''; ?>">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Country *</label>
                                    <input type="text" name="shipping_country" id="shipping_country" value="<?php echo $_POST['shipping_country'] ?? ''; ?>">
                                </div>
                                <div class="form-group">
                                    <label>ZIP/Postal Code *</label>
                                    <input type="text" name="shipping_zip" id="shipping_zip" value="<?php echo $_POST['shipping_zip'] ?? ''; ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Method -->
                    <div class="form-section">
                        <h2><i class="fas fa-truck"></i> Shipping Method</h2>
                        <div class="shipping-methods-list">
                            <?php foreach ($shippingMethods as $index => $method): 
                                $methodCost = calculateCartShipping($cartItems, $method['id']);
                            ?>
                                <label class="radio-card">
                                    <input type="radio" name="shipping_method" value="<?php echo $method['id']; ?>" 
                                           <?php echo $index === 0 ? 'checked' : ''; ?>
                                           data-cost="<?php echo $methodCost; ?>"
                                           onchange="updateOrderSummary()">
                                    <div class="radio-content">
                                        <strong><?php echo htmlspecialchars($method['name']); ?></strong>
                                        <span class="shipping-cost"><?php echo formatPrice($methodCost); ?></span>
                                        <?php if ($method['estimated_days']): ?>
                                            <small><?php echo htmlspecialchars($method['estimated_days']); ?></small>
                                        <?php endif; ?>
                                        <?php if ($method['description']): ?>
                                            <p><?php echo htmlspecialchars($method['description']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="form-section">
                        <h2><i class="fas fa-credit-card"></i> Payment Method</h2>
                        <div class="payment-methods-list">
                            <?php foreach ($paymentProviders as $index => $provider): ?>
                                <label class="radio-card">
                                    <input type="radio" name="payment_provider" value="<?php echo $provider['id']; ?>" 
                                           <?php echo $index === 0 ? 'checked' : ''; ?>>
                                    <div class="radio-content">
                                        <strong><?php echo htmlspecialchars($provider['name']); ?></strong>
                                        <?php if ($provider['description']): ?>
                                            <p><?php echo htmlspecialchars($provider['description']); ?></p>
                                        <?php endif; ?>
                                        <?php if ($provider['additional_fee'] > 0): ?>
                                            <small class="fee-notice">
                                                Additional fee: <?php echo formatPrice($provider['additional_fee']); ?>
                                                <?php echo $provider['fee_type'] === 'percentage' ? '%' : ''; ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Order Notes -->
                    <div class="form-section">
                        <h2><i class="fas fa-comment"></i> Order Notes (Optional)</h2>
                        <div class="form-group">
                            <textarea name="notes" rows="4" placeholder="Any special instructions for your order..."><?php echo $_POST['notes'] ?? ''; ?></textarea>
                        </div>
                    </div>

                    <!-- Coupon Code -->
                    <div class="form-section">
                        <h2><i class="fas fa-tag"></i> Discount Coupon</h2>
                        <div class="form-group">
                            <input type="text" name="coupon_code" placeholder="Enter coupon code" value="<?php echo $_POST['coupon_code'] ?? ''; ?>">
                        </div>
                    </div>
                </form>
            </div>

            <!-- Order Summary -->
            <div class="order-summary-section">
                <div class="order-summary-sticky">
                    <h2>Order Summary</h2>
                    
                    <div class="summary-items">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="summary-item">
                                <span class="item-name">
                                    <?php echo htmlspecialchars($item['name']); ?> x <?php echo $item['quantity']; ?>
                                </span>
                                <span class="item-price">
                                    <?php echo formatPrice($item['price'] * $item['quantity']); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="summary-totals">
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span id="summarySubtotal"><?php echo formatPrice($subtotal); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Shipping:</span>
                            <span id="summaryShipping"><?php echo formatPrice(calculateCartShipping($cartItems, $shippingMethods[0]['id'])); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Tax:</span>
                            <span id="summaryTax"><?php echo formatPrice(calculateTax($subtotal)); ?></span>
                        </div>
                        <div class="summary-row total">
                            <span>Total:</span>
                            <span id="summaryTotal">
                                <?php echo formatPrice($subtotal + calculateCartShipping($cartItems, $shippingMethods[0]['id']) + calculateTax($subtotal)); ?>
                            </span>
                        </div>
                    </div>

                    <button type="submit" form="checkoutForm" class="btn btn-primary btn-block btn-large">
                        <i class="fas fa-lock"></i> Place Order
                    </button>

                    <div class="secure-checkout">
                        <i class="fas fa-shield-alt"></i> Secure Checkout
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleShippingAddress() {
    const checkbox = document.getElementById('sameAsBilling');
    const fields = document.getElementById('shippingAddressFields');
    
    if (checkbox.checked) {
        fields.style.display = 'none';
        document.querySelectorAll('#shippingAddressFields input').forEach(input => {
            input.removeAttribute('required');
        });
    } else {
        fields.style.display = 'block';
        ['shipping_address', 'shipping_city', 'shipping_country', 'shipping_zip'].forEach(id => {
            document.getElementById(id).setAttribute('required', 'required');
        });
    }
}

function updateOrderSummary() {
    const selectedShipping = document.querySelector('input[name="shipping_method"]:checked');
    const shippingCost = parseFloat(selectedShipping.dataset.cost);
    const subtotal = <?php echo $subtotal; ?>;
    const taxRate = <?php echo DEFAULT_TAX_RATE; ?> / 100;
    
    const tax = subtotal * taxRate;
    const total = subtotal + shippingCost + tax;
    
    document.getElementById('summaryShipping').textContent = '<?php echo CURRENCY_SYMBOL; ?>' + shippingCost.toFixed(2);
    document.getElementById('summaryTotal').textContent = '<?php echo CURRENCY_SYMBOL; ?>' + total.toFixed(2);
}
</script>
