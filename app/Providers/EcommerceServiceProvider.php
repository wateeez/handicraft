<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class EcommerceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Load e-commerce configuration
        $this->mergeConfigFrom(
            __DIR__.'/../../config/ecommerce.php', 'ecommerce'
        );
    }

    public function boot(): void
    {
        // Load Session helper class
        if (file_exists(app_path('Helpers/SessionHelper.php'))) {
            require_once app_path('Helpers/SessionHelper.php');
        }
        
        // Load helper functions
        if (file_exists(app_path('Helpers/functions.php'))) {
            require_once app_path('Helpers/functions.php');
        }
        
        // Define constants for backward compatibility
        if (!defined('APP_NAME')) {
            define('APP_NAME', config('ecommerce.app_name'));
            define('APP_URL', config('ecommerce.app_url'));
            define('UPLOAD_DIR', config('ecommerce.upload_dir'));
            define('MAX_FILE_SIZE', config('ecommerce.max_file_size'));
            define('PRODUCTS_PER_PAGE', config('ecommerce.products_per_page'));
            define('ADMIN_ITEMS_PER_PAGE', config('ecommerce.admin_items_per_page'));
            define('DIMENSIONAL_FACTOR', config('ecommerce.dimensional_factor'));
            define('PACKAGING_BUFFER', config('ecommerce.packaging_buffer'));
            define('DEFAULT_TAX_RATE', config('ecommerce.default_tax_rate'));
            define('CURRENCY_SYMBOL', config('ecommerce.currency_symbol'));
            define('CURRENCY_CODE', config('ecommerce.currency_code'));
        }
    }
}
