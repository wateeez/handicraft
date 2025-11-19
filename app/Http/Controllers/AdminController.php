<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function login()
    {
        if (Session::has('admin_id')) {
            return redirect()->route('admin.dashboard');
        }
        
        ob_start();
        include resource_path('views/admin/login.php');
        $content = ob_get_clean();
        return response($content);
    }

    public function loginPost(Request $request)
    {
        require_once app_path('Helpers/functions.php');
        
        $username = $request->input('username');
        $password = $request->input('password');
        
        $admin = DB::table('admin_users')
            ->where(function($query) use ($username) {
                $query->where('username', $username)
                      ->orWhere('email', $username);
            })
            ->where('is_active', 1)
            ->first();
        
        if ($admin && Hash::check($password, $admin->password)) {
            Session::put('admin_id', $admin->id);
            Session::put('admin_name', $admin->full_name);
            Session::put('admin_role', $admin->role);
            Session::flash('success', 'Welcome back, ' . $admin->full_name);
            return redirect()->route('admin.dashboard');
        }
        
        Session::flash('error', 'Invalid credentials');
        return redirect()->route('admin.login');
    }

    public function logout()
    {
        Session::flush();
        return redirect()->route('admin.login');
    }

    public function dashboard()
    {
        $this->requireLogin();
        return $this->renderAdminPage('dashboard');
    }

    public function products()
    {
        $this->requireLogin();
        return $this->renderAdminPage('products');
    }

    public function addProduct()
    {
        $this->requireLogin();
        return $this->renderAdminPage('add-product');
    }

    public function bulkUpload()
    {
        $this->requireLogin();
        return $this->renderAdminPage('bulk-upload');
    }

    public function categories()
    {
        $this->requireLogin();
        return $this->renderAdminPage('categories');
    }

    public function shipping()
    {
        $this->requireLogin();
        return $this->renderAdminPage('shipping');
    }

    // Category POST handlers
    public function addCategory(Request $request)
    {
        $this->requireLogin();
        require_once app_path('Helpers/functions.php');
        
        $name = $request->input('name');
        $slug = $request->input('slug');
        $description = $request->input('description');
        $display_order = $request->input('display_order', 0);
        $is_active = $request->has('is_active') ? 1 : 0;
        
        // Auto-generate slug if not provided
        if (empty($slug)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        }
        
        // Handle image upload
        $image = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/categories'), $filename);
            $image = 'uploads/categories/' . $filename;
        }
        
        DB::table('categories')->insert([
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'image' => $image,
            'display_order' => $display_order,
            'is_active' => $is_active,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        Session::flash('success', 'Category added successfully');
        return redirect()->route('admin.categories');
    }

    public function deleteCategory(Request $request)
    {
        $this->requireLogin();
        $id = $request->input('id');
        
        DB::table('categories')->where('id', $id)->delete();
        
        Session::flash('success', 'Category deleted successfully');
        return redirect()->route('admin.categories');
    }

    public function addSubcategory(Request $request)
    {
        $this->requireLogin();
        
        $category_id = $request->input('category_id');
        $name = $request->input('name');
        $slug = $request->input('slug');
        $description = $request->input('description');
        $display_order = $request->input('display_order', 0);
        $is_active = $request->has('is_active') ? 1 : 0;
        
        // Auto-generate slug if not provided
        if (empty($slug)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        }
        
        DB::table('subcategories')->insert([
            'category_id' => $category_id,
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'display_order' => $display_order,
            'is_active' => $is_active,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        Session::flash('success', 'Subcategory added successfully');
        return redirect()->route('admin.categories');
    }

    public function deleteSubcategory(Request $request)
    {
        $this->requireLogin();
        $id = $request->input('id');
        
        DB::table('subcategories')->where('id', $id)->delete();
        
        Session::flash('success', 'Subcategory deleted successfully');
        return redirect()->route('admin.categories');
    }

    // Shipping POST handlers
    public function addShippingZone(Request $request)
    {
        $this->requireLogin();
        
        $name = $request->input('name');
        $countries = $request->input('countries');
        $is_active = $request->input('is_active', 1);
        
        DB::table('shipping_zones')->insert([
            'name' => $name,
            'countries' => $countries,
            'is_active' => $is_active,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        Session::flash('success', 'Shipping zone added successfully');
        return redirect()->route('admin.shipping');
    }

    public function deleteShippingZone(Request $request)
    {
        $this->requireLogin();
        $id = $request->input('id');
        
        DB::table('shipping_zones')->where('id', $id)->delete();
        
        Session::flash('success', 'Shipping zone deleted successfully');
        return redirect()->route('admin.shipping');
    }

    public function addShippingRate(Request $request)
    {
        $this->requireLogin();
        
        $zone_id = $request->input('zone_id');
        $name = $request->input('name');
        $min_weight = $request->input('min_weight');
        $max_weight = $request->input('max_weight');
        $rate = $request->input('rate');
        $is_active = $request->input('is_active', 1);
        
        DB::table('shipping_rates')->insert([
            'zone_id' => $zone_id,
            'name' => $name,
            'min_weight' => $min_weight,
            'max_weight' => $max_weight,
            'rate' => $rate,
            'is_active' => $is_active,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        Session::flash('success', 'Shipping rate added successfully');
        return redirect()->route('admin.shipping');
    }

    public function deleteShippingRate(Request $request)
    {
        $this->requireLogin();
        $id = $request->input('id');
        
        DB::table('shipping_rates')->where('id', $id)->delete();
        
        Session::flash('success', 'Shipping rate deleted successfully');
        return redirect()->route('admin.shipping');
    }

    // Product POST handlers
    public function addProductPost(Request $request)
    {
        $this->requireLogin();
        require_once app_path('Helpers/functions.php');
        
        $name = $request->input('name');
        $slug = $request->input('slug');
        $sku = $request->input('sku');
        $category_id = $request->input('category_id');
        $subcategory_id = $request->input('subcategory_id');
        $description = $request->input('description');
        $short_description = $request->input('short_description');
        $price = $request->input('price');
        $sale_price = $request->input('compare_price'); // compare_price maps to sale_price
        $cost_price = $request->input('cost_price');
        $stock_quantity = $request->input('stock'); // stock maps to stock_quantity
        $actual_weight = $request->input('weight'); // weight maps to actual_weight
        $length = $request->input('length');
        $width = $request->input('width');
        $height = $request->input('height');
        $is_featured = $request->has('is_featured') ? 1 : 0;
        $is_active = $request->has('is_active') ? 1 : 0;
        
        // Auto-generate slug if not provided
        if (empty($slug)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        }
        
        // Ensure slug is unique
        $originalSlug = $slug;
        $counter = 1;
        while (DB::table('products')->where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        // Ensure SKU is unique
        $originalSku = $sku;
        $skuCounter = 1;
        while (DB::table('products')->where('sku', $sku)->exists()) {
            $sku = $originalSku . '-' . $skuCounter;
            $skuCounter++;
        }
        
        // Validate that at least one image is uploaded
        if (!$request->hasFile('images')) {
            Session::flash('error', 'Please upload at least one product image');
            return redirect()->back()->withInput();
        }
        
        // Handle multiple image uploads
        $primaryImage = null;
        $uploadedImages = [];
        
        if ($request->hasFile('images')) {
            $files = $request->file('images');
            foreach ($files as $index => $file) {
                if ($file->isValid()) {
                    $filename = time() . '_' . $index . '_' . $file->getClientOriginalName();
                    $file->move(public_path('uploads/products'), $filename);
                    $imagePath = 'uploads/products/' . $filename;
                    $uploadedImages[] = $imagePath;
                    
                    // Set first image as primary
                    if ($index === 0) {
                        $primaryImage = $imagePath;
                    }
                }
            }
        }
        
        // Insert product
        $productId = DB::table('products')->insertGetId([
            'name' => $name,
            'slug' => $slug,
            'sku' => $sku,
            'category_id' => $category_id,
            'subcategory_id' => $subcategory_id,
            'description' => $description,
            'short_description' => $short_description,
            'price' => $price,
            'sale_price' => $sale_price,
            'cost_price' => $cost_price,
            'stock_quantity' => $stock_quantity,
            'actual_weight' => $actual_weight,
            'length' => $length,
            'width' => $width,
            'height' => $height,
            'is_featured' => $is_featured,
            'is_active' => $is_active,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Insert product images
        foreach ($uploadedImages as $index => $imagePath) {
            DB::table('product_images')->insert([
                'product_id' => $productId,
                'image_path' => $imagePath,
                'is_primary' => ($index === 0) ? 1 : 0,
                'display_order' => $index,
                'created_at' => now()
            ]);
        }
        
        Session::flash('success', 'Product added successfully with ' . count($uploadedImages) . ' image(s)');
        return redirect()->route('admin.products');
    }

    public function editProductPost(Request $request)
    {
        $this->requireLogin();
        $id = $request->input('id');
        
        $data = [
            'name' => $request->input('name'),
            'slug' => $request->input('slug'),
            'sku' => $request->input('sku'),
            'category_id' => $request->input('category_id'),
            'subcategory_id' => $request->input('subcategory_id'),
            'description' => $request->input('description'),
            'short_description' => $request->input('short_description'),
            'price' => $request->input('price'),
            'sale_price' => $request->input('compare_price'),
            'cost_price' => $request->input('cost_price'),
            'stock_quantity' => $request->input('stock'),
            'actual_weight' => $request->input('weight'),
            'length' => $request->input('length'),
            'width' => $request->input('width'),
            'height' => $request->input('height'),
            'is_featured' => $request->has('is_featured') ? 1 : 0,
            'is_active' => $request->has('is_active') ? 1 : 0,
            'updated_at' => now()
        ];
        
        // Handle multiple image uploads
        if ($request->hasFile('images')) {
            $files = $request->file('images');
            $uploadedImages = [];
            
            foreach ($files as $index => $file) {
                if ($file->isValid()) {
                    $filename = time() . '_' . $index . '_' . $file->getClientOriginalName();
                    $file->move(public_path('uploads/products'), $filename);
                    $imagePath = 'uploads/products/' . $filename;
                    $uploadedImages[] = $imagePath;
                }
            }
            
            // Insert new product images
            foreach ($uploadedImages as $index => $imagePath) {
                // Check if this is the first image across all product images
                $existingCount = DB::table('product_images')
                    ->where('product_id', $id)
                    ->count();
                    
                DB::table('product_images')->insert([
                    'product_id' => $id,
                    'image_path' => $imagePath,
                    'is_primary' => ($existingCount === 0 && $index === 0) ? 1 : 0,
                    'display_order' => $existingCount + $index,
                    'created_at' => now()
                ]);
            }
        }
        
        DB::table('products')->where('id', $id)->update($data);
        
        Session::flash('success', 'Product updated successfully');
        return redirect()->route('admin.products');
    }

    public function deleteProduct(Request $request)
    {
        $this->requireLogin();
        $id = $request->input('id');
        
        DB::table('products')->where('id', $id)->delete();
        
        Session::flash('success', 'Product deleted successfully');
        return redirect()->route('admin.products');
    }

    public function bulkUploadPost(Request $request)
    {
        $this->requireLogin();
        require_once app_path('Helpers/functions.php');
        
        if (!$request->hasFile('csv_file')) {
            Session::flash('error', 'Please select a CSV file');
            return redirect()->route('admin.products.bulk');
        }
        
        $file = $request->file('csv_file');
        $path = $file->getRealPath();
        
        $csv = array_map('str_getcsv', file($path));
        $headers = array_shift($csv);
        
        $imported = 0;
        $errors = [];
        
        foreach ($csv as $index => $row) {
            if (count($row) != count($headers)) {
                $errors[] = "Row " . ($index + 2) . ": Invalid column count";
                continue;
            }
            
            $data = array_combine($headers, $row);
            
            try {
                DB::table('products')->insert([
                    'name' => $data['name'] ?? '',
                    'slug' => $data['slug'] ?? '',
                    'sku' => $data['sku'] ?? '',
                    'category_id' => $data['category_id'] ?? null,
                    'subcategory_id' => $data['subcategory_id'] ?? null,
                    'description' => $data['description'] ?? '',
                    'short_description' => $data['short_description'] ?? '',
                    'price' => $data['price'] ?? 0,
                    'sale_price' => $data['compare_price'] ?? null,
                    'cost_price' => $data['cost_price'] ?? null,
                    'stock_quantity' => $data['stock'] ?? 0,
                    'actual_weight' => $data['weight'] ?? null,
                    'length' => $data['length'] ?? null,
                    'width' => $data['width'] ?? null,
                    'height' => $data['height'] ?? null,
                    'is_featured' => $data['is_featured'] ?? 0,
                    'is_active' => $data['is_active'] ?? 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
            }
        }
        
        if ($imported > 0) {
            Session::flash('success', "Successfully imported {$imported} products");
        }
        
        if (!empty($errors)) {
            Session::flash('error', implode('<br>', array_slice($errors, 0, 10)));
        }
        
        return redirect()->route('admin.products.bulk');
    }

    public function editProduct($id)
    {
        $this->requireLogin();
        
        // Set the ID in GET array for the legacy view to access
        $_GET['id'] = $id;
        
        return $this->renderAdminPage('edit-product');
    }

    public function exportProducts()
    {
        $this->requireLogin();
        
        $products = DB::table('products')->get();
        
        $filename = 'products_export_' . date('Y-m-d_His') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Headers
        fputcsv($output, [
            'id', 'name', 'slug', 'sku', 'category_id', 'subcategory_id',
            'description', 'short_description', 'price', 'compare_price', 'cost_price',
            'stock', 'weight', 'length', 'width', 'height', 'is_featured', 'is_active'
        ]);
        
        // Data
        foreach ($products as $product) {
            fputcsv($output, [
                $product->id,
                $product->name,
                $product->slug,
                $product->sku,
                $product->category_id,
                $product->subcategory_id,
                $product->description,
                $product->short_description,
                $product->price,
                $product->compare_price,
                $product->cost_price,
                $product->stock,
                $product->weight,
                $product->length,
                $product->width,
                $product->height,
                $product->is_featured,
                $product->is_active
            ]);
        }
        
        fclose($output);
        exit;
    }

    public function downloadTemplate()
    {
        $filename = 'product_import_template.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Headers
        fputcsv($output, [
            'name', 'slug', 'sku', 'category_id', 'subcategory_id',
            'description', 'short_description', 'price', 'compare_price', 'cost_price',
            'stock', 'weight', 'length', 'width', 'height', 'is_featured', 'is_active'
        ]);
        
        // Sample row
        fputcsv($output, [
            'Sample Product',
            'sample-product',
            'SKU001',
            '1',
            '1',
            'This is a sample product description',
            'Short description',
            '99.99',
            '149.99',
            '50.00',
            '100',
            '0.5',
            '10',
            '8',
            '5',
            '0',
            '1'
        ]);
        
        fclose($output);
        exit;
    }

    public function getSubcategories(Request $request)
    {
        $categoryId = $request->input('category_id');
        
        $subcategories = DB::table('subcategories')
            ->where('category_id', $categoryId)
            ->where('is_active', 1)
            ->get();
        
        return response()->json($subcategories);
    }

    public function orders()
    {
        $this->requireLogin();
        return $this->renderAdminPage('orders');
    }

    public function orderDetail($id)
    {
        $this->requireLogin();
        return $this->renderAdminPage('order-detail');
    }

    public function updateOrderStatus(Request $request)
    {
        $this->requireLogin();
        
        $orderId = $request->input('order_id');
        $status = $request->input('status');
        
        DB::table('orders')
            ->where('id', $orderId)
            ->update([
                'status' => $status,
                'updated_at' => now()
            ]);
        
        Session::flash('success', 'Order status updated successfully');
        return redirect()->route('admin.orders.detail', $orderId);
    }

    private function requireLogin()
    {
        if (!Session::has('admin_id')) {
            redirect()->route('admin.login')->send();
            exit;
        }
    }

    private function renderAdminPage($page)
    {
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
        
        ob_start();
        include resource_path("views/admin/{$page}.php");
        $content = ob_get_clean();
        
        return response($content);
    }
}
