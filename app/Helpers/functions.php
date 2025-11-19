<?php
// Helper Functions

// Database wrapper for Laravel
if (!function_exists('getDbInstance')) {
    function getDbInstance() {
        return new class {
            public function fetchAll($sql, $params = []) {
                $results = \Illuminate\Support\Facades\DB::select($sql, $params);
                return array_map(function($item) {
                    return (array) $item;
                }, $results);
            }
            
            public function fetchOne($sql, $params = []) {
                $result = \Illuminate\Support\Facades\DB::select($sql, $params);
                return !empty($result) ? (array) $result[0] : false;
            }
            
            public function execute($sql, $params = []) {
                return \Illuminate\Support\Facades\DB::statement($sql, $params);
            }
            
            public function lastInsertId() {
                return \Illuminate\Support\Facades\DB::getPdo()->lastInsertId();
            }
        };
    }
}

if (!function_exists('sanitize')) {
    function sanitize($data) {
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('ecommerce_redirect')) {
    function ecommerce_redirect($url) {
        header("Location: $url");
        exit;
    }
}

if (!function_exists('formatPrice')) {
    function formatPrice($price) {
        return CURRENCY_SYMBOL . number_format($price, 2);
    }
}

if (!function_exists('generateSlug')) {
    function generateSlug($string) {
        $string = strtolower(trim($string));
        $string = preg_replace('/[^a-z0-9-]/', '-', $string);
        $string = preg_replace('/-+/', '-', $string);
        return trim($string, '-');
    }
}

if (!function_exists('generateOrderNumber')) {
    function generateOrderNumber() {
        return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }
}

if (!function_exists('uploadImage')) {
    function uploadImage($file, $directory = 'products') {
        $uploadDir = UPLOAD_DIR . $directory . '/';
        
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            return ['success' => false, 'message' => 'Invalid file type'];
        }
        
        if ($file['size'] > MAX_FILE_SIZE) {
            return ['success' => false, 'message' => 'File too large'];
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return ['success' => true, 'filename' => $directory . '/' . $filename];
        }
        
        return ['success' => false, 'message' => 'Upload failed'];
    }
}

if (!function_exists('deleteImage')) {
    function deleteImage($filename) {
        $filepath = UPLOAD_DIR . $filename;
        if (file_exists($filepath)) {
            unlink($filepath);
            return true;
        }
        return false;
    }
}

if (!function_exists('calculateVolumetricWeight')) {
    function calculateVolumetricWeight($length, $width, $height) {
        // Add packaging buffer (8cm to each dimension)
        $shippingLength = $length + PACKAGING_BUFFER;
        $shippingWidth = $width + PACKAGING_BUFFER;
        $shippingHeight = $height + PACKAGING_BUFFER;
        
        // Calculate volumetric weight: (L x W x H) / Dimensional Factor
        $volumetricWeight = ($shippingLength * $shippingWidth * $shippingHeight) / DIMENSIONAL_FACTOR;
        
        return round($volumetricWeight, 2);
    }
}

if (!function_exists('calculateShippingCost')) {
    function calculateShippingCost($productId, $shippingMethodId) {
    $db = getDbInstance();
    
    // Get product details
    $product = $db->fetchOne(
        "SELECT actual_weight, shipping_length, shipping_width, shipping_height FROM products WHERE id = ?",
        [$productId]
    );
    
    if (!$product) {
        return 0;
    }
    
    // Calculate volumetric weight
    $volumetricWeight = calculateVolumetricWeight(
        $product['shipping_length'],
        $product['shipping_width'],
        $product['shipping_height']
    );
    
    // Use the greater of actual weight or volumetric weight
    $chargeableWeight = max($product['actual_weight'], $volumetricWeight);
    
    // Get shipping rate for this weight and method
    $rate = $db->fetchOne(
        "SELECT price_per_kg_actual, price_per_kg_volumetric, base_price 
         FROM shipping_rates 
         WHERE shipping_method_id = ? 
         AND min_weight <= ? 
         AND max_weight >= ?
         ORDER BY min_weight ASC
         LIMIT 1",
        [$shippingMethodId, $chargeableWeight, $chargeableWeight]
    );
    
    if (!$rate) {
        return 0;
    }
    
    // Determine which price to use based on which weight is greater
    if ($product['actual_weight'] > $volumetricWeight) {
        $shippingCost = $rate['base_price'] + ($chargeableWeight * $rate['price_per_kg_actual']);
    } else {
        $shippingCost = $rate['base_price'] + ($chargeableWeight * $rate['price_per_kg_volumetric']);
    }
    
    return round($shippingCost, 2);
    }
}

if (!function_exists('calculateCartShipping')) {
    function calculateCartShipping($cartItems, $shippingMethodId) {
        $totalShipping = 0;
        
        foreach ($cartItems as $item) {
            $itemShipping = calculateShippingCost($item['product_id'], $shippingMethodId);
            $totalShipping += $itemShipping * $item['quantity'];
        }
        
        return round($totalShipping, 2);
    }
}

if (!function_exists('calculateTax')) {
    function calculateTax($amount, $taxRateId = null) {
        $db = getDbInstance();
        
        if ($taxRateId) {
            $taxRate = $db->fetchOne("SELECT rate FROM tax_rates WHERE id = ? AND is_active = 1", [$taxRateId]);
            $rate = $taxRate ? $taxRate['rate'] : DEFAULT_TAX_RATE;
        } else {
            $rate = DEFAULT_TAX_RATE;
        }
        
        return round($amount * ($rate / 100), 2);
    }
}

if (!function_exists('applyCoupon')) {
    function applyCoupon($code, $subtotal) {
    $db = getDbInstance();
    
    $coupon = $db->fetchOne(
        "SELECT * FROM discount_coupons 
         WHERE code = ? 
         AND is_active = 1 
         AND (start_date IS NULL OR start_date <= NOW())
         AND (end_date IS NULL OR end_date >= NOW())
         AND (usage_limit IS NULL OR usage_count < usage_limit)",
        [$code]
    );
    
    if (!$coupon) {
        return ['success' => false, 'message' => 'Invalid or expired coupon'];
    }
    
    if ($subtotal < $coupon['min_purchase_amount']) {
        return [
            'success' => false, 
            'message' => 'Minimum purchase amount not met: ' . formatPrice($coupon['min_purchase_amount'])
        ];
    }
    
    if ($coupon['discount_type'] === 'fixed') {
        $discount = $coupon['discount_value'];
    } else {
        $discount = $subtotal * ($coupon['discount_value'] / 100);
        if ($coupon['max_discount_amount'] && $discount > $coupon['max_discount_amount']) {
            $discount = $coupon['max_discount_amount'];
        }
    }
    
        return [
            'success' => true,
            'discount' => round($discount, 2),
            'coupon_id' => $coupon['id']
        ];
    }
}

if (!function_exists('getCart')) {
    function getCart() {
    $db = getDbInstance();
    $sessionId = session()->getId();
    
    $cart = $db->fetchOne(
        "SELECT id FROM carts WHERE session_id = ?",
        [$sessionId]
    );
    
    if (!$cart) {
        $db->execute(
            "INSERT INTO carts (session_id) VALUES (?)",
            [$sessionId]
        );
        $cartId = $db->lastInsertId();
        } else {
            $cartId = $cart['id'];
        }
        
        return $cartId;
    }
}

if (!function_exists('getCartItems')) {
    function getCartItems() {
    $db = getDbInstance();
    $cartId = getCart();
    
    return $db->fetchAll(
        "SELECT ci.*, p.name, p.slug, p.stock_quantity, 
                (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image
         FROM cart_items ci
         JOIN products p ON ci.product_id = p.id
         WHERE ci.cart_id = ?",
        [$cartId]
        );
    }
}

if (!function_exists('getCartCount')) {
    function getCartCount() {
    $items = getCartItems();
    $count = 0;
        foreach ($items as $item) {
            $count += $item['quantity'];
        }
        return $count;
    }
}

if (!function_exists('getCartTotal')) {
    function getCartTotal() {
    $items = getCartItems();
    $total = 0;
        foreach ($items as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        return $total;
    }
}

if (!function_exists('addToCart')) {
    function addToCart($productId, $quantity = 1) {
        $db = getDbInstance();
        $cartId = getCart();
        
        // Check if product exists and has stock
        $product = $db->fetchOne(
            "SELECT id, price, stock_quantity FROM products WHERE id = ? AND is_active = 1",
            [$productId]
        );
        
        if (!$product) {
            return ['success' => false, 'message' => 'Product not found'];
        }
        
        if ($product['stock_quantity'] < $quantity) {
            return ['success' => false, 'message' => 'Insufficient stock'];
        }
        
        // Check if item already in cart
        $existingItem = $db->fetchOne(
            "SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ?",
            [$cartId, $productId]
        );
        
        if ($existingItem) {
            $newQuantity = $existingItem['quantity'] + $quantity;
            if ($newQuantity > $product['stock_quantity']) {
                return ['success' => false, 'message' => 'Insufficient stock'];
            }
            
            $db->execute(
                "UPDATE cart_items SET quantity = ? WHERE id = ?",
                [$newQuantity, $existingItem['id']]
            );
        } else {
            $db->execute(
                "INSERT INTO cart_items (cart_id, product_id, quantity, price) VALUES (?, ?, ?, ?)",
                [$cartId, $productId, $quantity, $product['price']]
            );
        }
        
        return ['success' => true, 'message' => 'Product added to cart'];
    }
}

if (!function_exists('updateCartItem')) {
    function updateCartItem($cartItemId, $quantity) {
        $db = getDbInstance();
        
        if ($quantity <= 0) {
            return removeCartItem($cartItemId);
        }
        
        $db->execute(
            "UPDATE cart_items SET quantity = ? WHERE id = ?",
            [$quantity, $cartItemId]
        );
        
        return ['success' => true];
    }
}

if (!function_exists('removeCartItem')) {
    function removeCartItem($cartItemId) {
        $db = getDbInstance();
        $db->execute("DELETE FROM cart_items WHERE id = ?", [$cartItemId]);
        return ['success' => true];
    }
}

if (!function_exists('clearCart')) {
    function clearCart() {
        $db = getDbInstance();
        $cartId = getCart();
        $db->execute("DELETE FROM cart_items WHERE cart_id = ?", [$cartId]);
    }
}

if (!function_exists('paginate')) {
    function paginate($total, $perPage, $currentPage = 1) {
        $totalPages = ceil($total / $perPage);
        $currentPage = max(1, min($currentPage, $totalPages));
        $offset = ($currentPage - 1) * $perPage;
        
        return [
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'offset' => $offset,
            'has_prev' => $currentPage > 1,
            'has_next' => $currentPage < $totalPages
        ];
    }
}

if (!function_exists('isEmail')) {
    function isEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}

if (!function_exists('hashPassword')) {
    function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}

if (!function_exists('verifyPassword')) {
    function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}
