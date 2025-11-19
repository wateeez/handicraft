<?php
$pageTitle = "Checkout";

// Get cart items
$cartItems = getCartItems();

if (empty($cartItems)) {
    redirect('?page=cart');
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Validate customer information
    $customerName = sanitize($_POST['customer_name'] ?? '');
    $customerEmail = sanitize($_POST['customer_email'] ?? '');
    $customerPhone = sanitize($_POST['customer_phone'] ?? '');
    
    // Billing address
    $billingAddress = sanitize($_POST['billing_address'] ?? '');
    $billingCity = sanitize($_POST['billing_city'] ?? '');
    $billingState = sanitize($_POST['billing_state'] ?? '');
    $billingCountry = sanitize($_POST['billing_country'] ?? '');
    $billingZip = sanitize($_POST['billing_zip'] ?? '');
    
    // Shipping address
    $sameAsBilling = isset($_POST['same_as_billing']);
    if ($sameAsBilling) {
        $shippingAddress = $billingAddress;
        $shippingCity = $billingCity;
        $shippingState = $billingState;
        $shippingCountry = $billingCountry;
        $shippingZip = $billingZip;
    } else {
        $shippingAddress = sanitize($_POST['shipping_address'] ?? '');
        $shippingCity = sanitize($_POST['shipping_city'] ?? '');
        $shippingState = sanitize($_POST['shipping_state'] ?? '');
        $shippingCountry = sanitize($_POST['shipping_country'] ?? '');
        $shippingZip = sanitize($_POST['shipping_zip'] ?? '');
    }
    
    $shippingMethodId = intval($_POST['shipping_method'] ?? 0);
    $paymentProviderId = intval($_POST['payment_provider'] ?? 0);
    $notes = sanitize($_POST['notes'] ?? '');
    $couponCode = sanitize($_POST['coupon_code'] ?? '');
    
    // Validation
    if (empty($customerName)) $errors[] = "Name is required";
    if (empty($customerEmail) || !isEmail($customerEmail)) $errors[] = "Valid email is required";
    if (empty($billingAddress)) $errors[] = "Billing address is required";
    if (empty($billingCity)) $errors[] = "Billing city is required";
    if (empty($billingCountry)) $errors[] = "Billing country is required";
    if (empty($billingZip)) $errors[] = "Billing ZIP code is required";
    if (empty($shippingMethodId)) $errors[] = "Please select a shipping method";
    if (empty($paymentProviderId)) $errors[] = "Please select a payment method";
    
    if (empty($errors)) {
        // Calculate shipping cost
        $shippingCost = calculateCartShipping($cartItems, $shippingMethodId);
        
        // Apply coupon if provided
        $discountAmount = 0;
        if ($couponCode) {
            $couponResult = applyCoupon($couponCode, $subtotal);
            if ($couponResult['success']) {
                $discountAmount = $couponResult['discount'];
            }
        }
        
        // Calculate tax
        $taxAmount = calculateTax($subtotal - $discountAmount);
        
        // Calculate total
        $totalAmount = $subtotal + $shippingCost + $taxAmount - $discountAmount;
        
        try {
            $db->beginTransaction();
            
            // Create order
            $orderNumber = generateOrderNumber();
            $sessionId = Session::getSessionId();
            
            $db->execute(
                "INSERT INTO orders (
                    order_number, session_id, customer_name, customer_email, customer_phone,
                    billing_address, billing_city, billing_state, billing_country, billing_zip,
                    shipping_address, shipping_city, shipping_state, shipping_country, shipping_zip,
                    subtotal, tax_amount, shipping_cost, discount_amount, total_amount,
                    shipping_method_id, payment_provider_id, coupon_code, notes, status, payment_status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending')",
                [
                    $orderNumber, $sessionId, $customerName, $customerEmail, $customerPhone,
                    $billingAddress, $billingCity, $billingState, $billingCountry, $billingZip,
                    $shippingAddress, $shippingCity, $shippingState, $shippingCountry, $shippingZip,
                    $subtotal, $taxAmount, $shippingCost, $discountAmount, $totalAmount,
                    $shippingMethodId, $paymentProviderId, $couponCode, $notes
                ]
            );
            
            $orderId = $db->lastInsertId();
            
            // Add order items
            foreach ($cartItems as $item) {
                $itemSubtotal = $item['price'] * $item['quantity'];
                $db->execute(
                    "INSERT INTO order_items (order_id, product_id, product_name, product_sku, quantity, price, subtotal)
                     VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [
                        $orderId, $item['product_id'], $item['name'], 
                        $db->fetchOne("SELECT sku FROM products WHERE id = ?", [$item['product_id']])['sku'],
                        $item['quantity'], $item['price'], $itemSubtotal
                    ]
                );
                
                // Update product stock
                $db->execute(
                    "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?",
                    [$item['quantity'], $item['product_id']]
                );
            }
            
            // Update coupon usage if applied
            if ($couponCode && isset($couponResult['coupon_id'])) {
                $db->execute(
                    "UPDATE discount_coupons SET usage_count = usage_count + 1 WHERE id = ?",
                    [$couponResult['coupon_id']]
                );
            }
            
            // Clear cart
            clearCart();
            
            $db->commit();
            
            // Store order number in session for success page
            Session::set('last_order_number', $orderNumber);
            
            redirect('?page=checkout-success');
            
        } catch (Exception $e) {
            $db->rollback();
            $errors[] = "Order processing failed. Please try again.";
            error_log("Checkout error: " . $e->getMessage());
        }
    }
    
    if ($errors) {
        Session::setFlash('error', implode('<br>', $errors));
    }
}
?>

<div class="checkout-page">
    <div class="container">
        <h1>Checkout</h1>

        <div class="checkout-content">
            <!-- Checkout Form -->
            <div class="checkout-form-section">
                <form method="POST" id="checkoutForm">
                    <!-- Customer Information -->
                    <div class="form-section">
                        <h2><i class="fas fa-user"></i> Customer Information</h2>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Full Name *</label>
                                <input type="text" name="customer_name" required value="<?php echo $_POST['customer_name'] ?? ''; ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Email Address *</label>
                                <input type="email" name="customer_email" required value="<?php echo $_POST['customer_email'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="tel" name="customer_phone" value="<?php echo $_POST['customer_phone'] ?? ''; ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Billing Address -->
                    <div class="form-section">
                        <h2><i class="fas fa-file-invoice"></i> Billing Address</h2>
                        <div class="form-group">
                            <label>Street Address *</label>
                            <input type="text" name="billing_address" required value="<?php echo $_POST['billing_address'] ?? ''; ?>">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>City *</label>
                                <input type="text" name="billing_city" required value="<?php echo $_POST['billing_city'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>State/Province</label>
                                <input type="text" name="billing_state" value="<?php echo $_POST['billing_state'] ?? ''; ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Country *</label>
                                <input type="text" name="billing_country" required value="<?php echo $_POST['billing_country'] ?? ''; ?>">
                            </div>
                            <div class="form-group">
                                <label>ZIP/Postal Code *</label>
                                <input type="text" name="billing_zip" required value="<?php echo $_POST['billing_zip'] ?? ''; ?>">
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
