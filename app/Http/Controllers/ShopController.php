<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class ShopController extends Controller
{
    public function index()
    {
        return $this->showPage('home');
    }

    public function products(Request $request)
    {
        return $this->showPage('products');
    }

    public function productDetail($slug)
    {
        return $this->showPage('product-detail', ['slug' => $slug]);
    }

    public function cart()
    {
        return $this->showPage('cart');
    }

    public function checkout()
    {
        return $this->showPage('checkout');
    }

    public function checkoutSuccess()
    {
        return $this->showPage('checkout-success');
    }

    public function blog()
    {
        return $this->showPage('blog');
    }

    public function blogDetail($slug)
    {
        return $this->showPage('blog-detail', ['slug' => $slug]);
    }

    public function faq()
    {
        return $this->showPage('faq');
    }

    public function contact()
    {
        return $this->showPage('contact');
    }

    public function about()
    {
        return $this->showPage('about');
    }

    private function showPage($page, $data = [])
    {
        // Load helper functions
        require_once app_path('Helpers/functions.php');
        
        // Create a database wrapper for legacy views
        $db = new class {
            public function fetchAll($sql, $params = []) {
                $results = DB::select($sql, $params);
                return array_map(function($item) {
                    return (array) $item;
                }, $results);
            }
            
            public function fetchOne($sql, $params = []) {
                $result = DB::select($sql, $params);
                return !empty($result) ? (array) $result[0] : false;
            }
            
            public function query($sql, $params = []) {
                return DB::statement($sql, $params);
            }
            
            public function execute($sql, $params = []) {
                return DB::statement($sql, $params);
            }
            
            public function lastInsertId() {
                return DB::getPdo()->lastInsertId();
            }
        };
        
        // Set variables for the view
        extract($data);
        
        ob_start();
        include resource_path("views/ecommerce/partials/header.php");
        include resource_path("views/ecommerce/{$page}.php");
        include resource_path("views/ecommerce/partials/footer.php");
        $content = ob_get_clean();
        
        return response($content);
    }

    public function addToCart(Request $request)
    {
        require_once app_path('Helpers/functions.php');
        
        $productId = $request->input('product_id');
        $quantity = $request->input('quantity', 1);
        
        $result = addToCart($productId, $quantity);
        return response()->json($result);
    }

    public function cartCount()
    {
        require_once app_path('Helpers/functions.php');
        
        $count = getCartCount();
        return response()->json(['count' => $count]);
    }

    public function applyCoupon(Request $request)
    {
        require_once app_path('Helpers/functions.php');
        
        $code = $request->input('coupon_code');
        $subtotal = getCartTotal();
        
        if ($code) {
            $result = applyCoupon($code, $subtotal);
            if ($result['success']) {
                Session::put('coupon_code', $code);
                Session::put('discount_amount', $result['discount']);
                return response()->json([
                    'success' => true,
                    'message' => 'Coupon applied! You save ' . formatPrice($result['discount']),
                    'discount' => $result['discount']
                ]);
            }
            return response()->json($result);
        }
        
        return response()->json(['success' => false, 'message' => 'Please enter a coupon code']);
    }

    public function updateCart(Request $request)
    {
        require_once app_path('Helpers/functions.php');
        
        $cartItemId = $request->input('cart_item_id');
        $quantity = $request->input('quantity', 1);
        
        $result = updateCartItem($cartItemId, $quantity);
        
        Session::flash('success', 'Cart updated successfully');
        return redirect()->route('shop.cart');
    }

    public function removeFromCart(Request $request)
    {
        require_once app_path('Helpers/functions.php');
        
        $cartItemId = $request->input('cart_item_id');
        
        $result = removeCartItem($cartItemId);
        
        Session::flash('success', 'Item removed from cart');
        return redirect()->route('shop.cart');
    }

    public function processCheckout(Request $request)
    {
        require_once app_path('Helpers/functions.php');
        
        // Validate request
        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email',
            'phone' => 'required',
            'address' => 'required',
            'city' => 'required',
            'state' => 'required',
            'zip' => 'required',
            'country' => 'required',
        ]);
        
        // Get cart items
        $cartItems = getCartItems();
        
        if (empty($cartItems)) {
            Session::flash('error', 'Your cart is empty');
            return redirect()->route('shop.cart');
        }
        
        // Calculate totals
        $subtotal = getCartTotal();
        $tax = calculateTax($subtotal);
        
        // Get shipping charge from selected method
        $shippingMethodId = $request->input('shipping_method');
        $shippingCharge = 0;
        if ($shippingMethodId) {
            // Calculate cart weight
            $totalWeight = 0;
            foreach ($cartItems as $item) {
                $product = DB::table('products')->where('id', $item['product_id'])->first();
                if ($product && $product->actual_weight) {
                    $totalWeight += ($product->actual_weight * $item['quantity']);
                }
            }
            
            // Get shipping rate based on weight
            $shippingRate = DB::table('shipping_rates')
                ->where('shipping_method_id', $shippingMethodId)
                ->where('min_weight', '<=', $totalWeight)
                ->where('max_weight', '>=', $totalWeight)
                ->first();
            
            if ($shippingRate) {
                $shippingCharge = $shippingRate->base_price;
                // Add weight-based charge if applicable
                if ($totalWeight > 0) {
                    $shippingCharge += ($totalWeight * $shippingRate->price_per_kg_actual);
                }
            }
        }
        
        $discount = Session::get('discount_amount', 0);
        $total = $subtotal + $tax + $shippingCharge - $discount;
        
        // Get payment provider name
        $paymentProviderId = $request->input('payment_provider');
        $paymentMethod = 'Cash on Delivery';
        if ($paymentProviderId) {
            $provider = DB::table('payment_providers')
                ->where('id', $paymentProviderId)
                ->where('is_active', 1)
                ->first();
            if ($provider) {
                $paymentMethod = $provider->name;
            }
        }
        
        try {
            // Generate unique order number
            $orderNumber = 'ORD-' . strtoupper(uniqid());
            
            // Create order
            $orderId = DB::table('orders')->insertGetId([
                'order_number' => $orderNumber,
                'customer_name' => $request->first_name . ' ' . $request->last_name,
                'customer_email' => $request->email,
                'customer_phone' => $request->phone,
                'billing_address' => $request->address,
                'billing_city' => $request->city,
                'billing_state' => $request->state,
                'billing_zip' => $request->zip,
                'billing_country' => $request->country,
                'shipping_address' => $request->address,
                'shipping_city' => $request->city,
                'shipping_state' => $request->state,
                'shipping_zip' => $request->zip,
                'shipping_country' => $request->country,
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'shipping_cost' => $shippingCharge,
                'discount_amount' => $discount,
                'total_amount' => $total,
                'shipping_method_id' => $shippingMethodId,
                'payment_provider_id' => $paymentProviderId,
                'coupon_code' => Session::get('coupon_code', null),
                'payment_status' => 'Pending',
                'status' => 'Pending',
                'notes' => $request->input('notes', ''),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Create order items
            foreach ($cartItems as $item) {
                // Get product SKU
                $product = DB::table('products')->where('id', $item['product_id'])->first();
                
                DB::table('order_items')->insert([
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['name'],
                    'product_sku' => $product ? $product->sku : '',
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $item['price'] * $item['quantity'],
                    'created_at' => now(),
                ]);
                
                // Update product stock
                DB::table('products')
                    ->where('id', $item['product_id'])
                    ->decrement('stock_quantity', $item['quantity']);
            }
            
            // Clear cart
            if (Session::has('user_id')) {
                DB::table('cart')->where('user_id', Session::get('user_id'))->delete();
            } else {
                Session::forget('cart');
            }
            
            // Clear coupon and shipping
            Session::forget(['coupon_code', 'discount_amount', 'shipping_charge']);
            
            Session::flash('success', 'Order placed successfully! Your order number is: ' . $orderNumber);
            Session::flash('order_number', $orderNumber);
            return redirect()->route('shop.checkout.success');
            
        } catch (\Exception $e) {
            Session::flash('error', 'Failed to process order: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }
}
