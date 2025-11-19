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
        
        // Process checkout logic here
        // This would typically create an order, process payment, etc.
        
        Session::flash('success', 'Order placed successfully!');
        return redirect()->route('shop.checkout.success');
    }
}
