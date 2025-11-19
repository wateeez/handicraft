# E-Commerce Website - PHP & MySQL

A comprehensive e-commerce platform built with PHP and MySQL, featuring advanced shipping calculation, product management, and a full admin panel.

## Features

### Frontend Features
- **Homepage** with featured products and categories
- **Product Listing** with filters, search, and sorting
- **Product Detail Pages** with image gallery and specifications
- **Shopping Cart** with quantity management
- **Checkout System** with multiple payment and shipping options
- **Blog** system with posts and categories
- **FAQ** page with categorized questions
- **Contact** page with message submission
- Responsive design with mobile support

### Admin Panel Features
- **Dashboard** with statistics and analytics
- **Product Management** (CRUD operations)
- **Category & Subcategory Management**
- **Bulk Product Upload** via CSV
- **Product Export** to CSV
- **Order Management** with status tracking
- **Shipping Methods** and rate configuration
- **Discount Coupons** management
- **Tax Rate** configuration
- **Blog Post** management
- **FAQ** management
- **Customer Messages** inbox

### Advanced Shipping System
- **Multi-carrier Support** (DHL, Aramex, FedEx, etc.)
- **Automatic Dimensional Weight Calculation**
- Uses greater of actual weight or volumetric weight
- Automatic 8cm packaging buffer added to dimensions
- Weight-based pricing tiers
- Admin-configurable shipping rates

### Payment Integration
- Multiple payment provider support
- Cash on Delivery
- Credit/Debit Card (Stripe integration ready)
- PayPal (integration ready)
- Bank Transfer

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- mod_rewrite enabled (for clean URLs)

### Setup Instructions

1. **Clone or Download** the project files to your web server directory

2. **Create Database**
   ```sql
   CREATE DATABASE ecommerce_db;
   ```

3. **Import Database Schema**
   ```bash
   mysql -u root -p ecommerce_db < database/schema.sql
   ```

4. **Configure Database Connection**
   Edit `config/database_config.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'ecommerce_db');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   ```

5. **Set File Permissions**
   ```bash
   chmod 755 public/uploads
   chmod 755 storage
   ```

6. **Access the Application**
   - Frontend: `http://yoursite.com/shop.php`
   - Admin Panel: `http://yoursite.com/admin/login.php`

### Default Admin Credentials
- **Username:** admin
- **Password:** admin123

**Important:** Change these credentials immediately after first login!

## Directory Structure

```
├── admin/                  # Admin panel files
│   ├── includes/          # Admin header/footer
│   ├── api/               # Admin API endpoints
│   ├── dashboard.php      # Admin dashboard
│   ├── products.php       # Product management
│   ├── categories.php     # Category management
│   ├── orders.php         # Order management
│   ├── shipping.php       # Shipping configuration
│   ├── bulk-upload.php    # Bulk product upload
│   └── ...
├── config/                # Configuration files
│   └── database_config.php
├── database/              # Database schemas
│   └── schema.sql
├── includes/              # Core PHP classes
│   ├── Database.php       # Database handler
│   ├── Session.php        # Session manager
│   ├── functions.php      # Helper functions
│   └── init.php          # Application initializer
├── public/                # Public accessible files
│   ├── assets/           # CSS, JS, images
│   ├── uploads/          # Uploaded product images
│   ├── api/              # Frontend API endpoints
│   ├── index.php         # Main entry (Laravel)
│   └── shop.php          # E-commerce frontend
├── views/                 # Frontend views
│   ├── pages/            # Page templates
│   └── partials/         # Header, footer, etc.
└── README.md
```

## Usage Guide

### Adding Products

#### Manual Addition
1. Login to Admin Panel
2. Navigate to Products → Add Product
3. Fill in product details
4. Upload product images
5. Set dimensions and weight (for shipping calculation)
6. Click "Add Product"

#### Bulk Upload
1. Navigate to Products → Bulk Upload
2. Download CSV template
3. Fill in product data
4. Upload completed CSV file
5. System will import/update products automatically

### Shipping Calculation

The system uses an advanced shipping calculation method:

1. **Dimensional Weight Formula:**
   ```
   Volumetric Weight = (L × W × H) / 5000
   ```

2. **Packaging Buffer:**
   - 8cm automatically added to each dimension (L, W, H)
   - Not shown to customers, only used for shipping calculation

3. **Chargeable Weight:**
   - System compares actual weight vs volumetric weight
   - Uses the greater value for pricing

4. **Rate Calculation:**
   - Based on weight tier (0-5kg, 5-10kg, etc.)
   - Different rates for actual vs volumetric weight
   - Base price + (weight × rate)

### Managing Shipping Methods

1. Navigate to Admin → Shipping
2. Add new shipping method (DHL, Aramex, etc.)
3. Configure rate tiers:
   - Weight range (min/max)
   - Price per kg for actual weight
   - Price per kg for volumetric weight
   - Base/flat rate
4. Save configuration

### Discount Coupons

1. Navigate to Admin → Coupons
2. Create new coupon:
   - Coupon code
   - Discount type (fixed amount or percentage)
   - Discount value
   - Minimum purchase amount
   - Usage limits
   - Validity dates
3. Customers can apply coupons at checkout

### Order Management

1. View all orders in Admin → Orders
2. Update order status:
   - Pending
   - Processing
   - Shipped (add tracking number)
   - Delivered
   - Cancelled/Refunded
3. Update payment status
4. Print order details/invoices

## CSV Import Format

### Required Columns
- `name` - Product name
- `sku` - Unique SKU
- `category_slug` - Category identifier
- `price` - Regular price
- `stock_quantity` - Available stock

### Optional Columns
- `subcategory_slug`
- `sale_price`
- `cost_price`
- `description`
- `short_description`
- `length`, `width`, `height` (cm)
- `actual_weight` (kg)
- `low_stock_threshold`
- `is_active` (1 or 0)
- `is_featured` (1 or 0)

## API Endpoints

### Frontend APIs
- `/api/cart-add.php` - Add item to cart
- `/api/cart-count.php` - Get cart item count
- `/api/apply-coupon.php` - Apply discount coupon

### Admin APIs
- `/admin/api/get-subcategories.php` - Get subcategories by category

## Security Features

- Password hashing (bcrypt)
- SQL injection prevention (prepared statements)
- XSS protection (input sanitization)
- CSRF protection (session validation)
- File upload validation
- Admin authentication required

## Customization

### Changing Dimensional Factor
Edit `config/database_config.php`:
```php
define('DIMENSIONAL_FACTOR', 5000); // Change this value
```

### Changing Packaging Buffer
```php
define('PACKAGING_BUFFER', 8); // Change from 8cm to your preference
```

### Changing Currency
```php
define('CURRENCY_SYMBOL', '$');
define('CURRENCY_CODE', 'USD');
```

### Changing Tax Rate
```php
define('DEFAULT_TAX_RATE', 15); // 15%
```

## Troubleshooting

### Images Not Uploading
- Check `public/uploads/` directory permissions (755)
- Verify `MAX_FILE_SIZE` in config
- Check PHP upload limits in php.ini

### Database Connection Error
- Verify credentials in `config/database_config.php`
- Ensure MySQL service is running
- Check database exists

### Admin Login Issues
- Verify default credentials
- Check `admin_users` table in database
- Clear browser cache/cookies

## Support

For issues or questions:
1. Check this documentation
2. Review error logs
3. Verify database schema is properly imported

## License

This project is open-source and available for commercial use.

## Credits

Built with:
- PHP 7.4+
- MySQL 5.7+
- Font Awesome Icons
- Pure CSS (no framework dependencies)

---

**Version:** 1.0.0  
**Last Updated:** November 2025
